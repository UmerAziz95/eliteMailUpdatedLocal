<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use ChargeBee\ChargeBee\Models\Customer;
use ChargeBee\ChargeBee\Exceptions\APIError;
// Notification
use App\Models\Notification;
class ProfileController extends Controller
{ 
    public function updateAddress(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'modalcountry' => 'required|string|max:255',
                'modalAddressAddress1' => 'required|string|max:255',
                'modalAddressAddress2' => 'nullable|string|max:255',
                'modalAddressLandmark' => 'nullable|string|max:255',
                'modalAddressCity' => 'required|string|max:255',
                'modalAddressState' => 'nullable|string|max:255',
                'modalAddressZipCode' => 'required|string|max:20',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = auth()->user();
            
            // Update user in our database
            $user->update([
                'billing_country' => $request->modalcountry,
                'billing_address' => $request->modalAddressAddress1,
                'billing_address2' => $request->modalAddressAddress2,
                'billing_landmark' => $request->modalAddressLandmark,
                'billing_city' => $request->modalAddressCity,
                'billing_state' => $request->modalAddressState,
                'billing_zip' => $request->modalAddressZipCode,
                'billing_company' => $request->modalAddressCompany,
            ]);

            // Get or determine ChargeBee customer ID
            $chargebee_customer_id = null;
            
            // First try to get it from user
            if ($user->chargebee_customer_id) {
                $chargebee_customer_id = $user->chargebee_customer_id;
            } else {
                // Try to get it from the latest order
                $latestOrder = Order::where('user_id', $user->id)
                    ->whereNotNull('chargebee_customer_id')
                    ->latest()
                    ->first();
                    
                if ($latestOrder) {
                    $chargebee_customer_id = $latestOrder->chargebee_customer_id;
                }
            }
            // Update customer data in ChargeBee or create a new customer
            if ($chargebee_customer_id) {
                // Update existing customer in ChargeBee
                try {

                    $updateData = [
                        "billing_address" => [
                            "first_name" => $user->name,
                            "company" => $request->modalAddressCompany ?? "",
                            "line1" => $request->modalAddressAddress1,
                            "line2" => $request->modalAddressAddress2 ?? "",
                            "city" => $request->modalAddressCity,
                            "state" => $request->modalAddressState ?? "",
                            "zip" => $request->modalAddressZipCode,
                            "country" => $this->shortName($request->modalcountry)
                        ]
                    ];
                    
                    // Log the address being sent to ChargeBee for debugging
                    // Log::info("Sending billing address update to ChargeBee", [
                    //     'customer_id' => $chargebee_customer_id,
                    //     'update_data' => $updateData
                    // ]);
                    
                    // Use the specific updateBillingInfo method as per documentation
                    // This is the correct method for updating billing address
                    // Convert country name to 2-letter country code as required by ChargeBee
                    $countryCode = $this->shortName($request->modalcountry);
                    
                    $result = Customer::updateBillingInfo($chargebee_customer_id, [
                        "billing_address" => [
                            "first_name" => $user->name,
                            "company" => $request->modalAddressCompany ?? "",
                            "line1" => $request->modalAddressAddress1,
                            "line2" => $request->modalAddressAddress2 ?? "",
                            "city" => $request->modalAddressCity,
                            "state" => $request->modalAddressState ?? "",
                            "zip" => $request->modalAddressZipCode,
                            "country" => $countryCode
                        ]
                    ]);
                    
                    // Log the ChargeBee response with proper values extraction
                    $customerResult = $result->customer();
                    // Log::info("ChargeBee update response", [
                    //     'customer_id' => $customerResult->id,
                    //     'first_name' => $customerResult->firstName,
                    //     'values' => $customerResult->getValues()
                    // ]);
                    // If successful, ensure user's chargebee_customer_id is set
                    if (!$user->chargebee_customer_id) {
                        $user->update(['chargebee_customer_id' => $chargebee_customer_id]);
                    }
                    
                    // Verify the update was successful by checking the customer object
                    $updatedCustomer = $result->customer();
                    
                    // Get the billing address properly from the customer object
                    // The billing address data is available in the values directly
                    $customerValues = $updatedCustomer->getValues();
                    $updatedBillingAddress = $customerValues['billing_address'] ?? null;
                    if(!$updatedBillingAddress) {
                        Log::info("ChargeBee customer billing address not saved", [
                            'user_id' => $user->id,
                            'chargebee_customer_id' => $chargebee_customer_id,
                            'customer_values' => $customerValues
                        ]);
                        $user->update(['billing_address_syn' => false]);
                    }else{
                        Log::info("ChargeBee customer billing address saved", [
                            'user_id' => $user->id,
                            'chargebee_customer_id' => $chargebee_customer_id,
                            'customer_values' => $customerValues
                        ]);
                        $user->update(['billing_address_syn' => true]);
                    }
                    Log::info("ChargeBee customer billing address updated", [
                        'user_id' => $user->id,
                        'chargebee_customer_id' => $chargebee_customer_id,
                        'updated_billing_address' => $updatedBillingAddress,
                        'customer_values' => $customerValues
                    ]);
                } catch (\ChargeBee\ChargeBee\Exceptions\APIError $e) {
                    $user->update(['billing_address_syn' => false]);
                    Notification::create([
                        'user_id' => $user->id,
                        'type' => 'billing_address_syn',
                        'title' => 'Billing Address Sync Failed',
                        'message' => "Failed to sync billing address with ChargeBee for user ID {$user->id}",
                        'data' => [
                            'error_message' => $e->getMessage(),
                            'ip_address' => request()->ip()
                        ]
                    ]);
                    // Specifically handle ChargeBee API errors
                    Log::error('ChargeBee API Error updating billing address: ' . $e->getMessage(), [
                        'user_id' => $user->id,
                        'chargebee_customer_id' => $chargebee_customer_id,
                        'api_error_code' => $e->getApiErrorCode(),
                        'http_status_code' => $e->getHttpStatusCode()
                    ]);
                } catch (\Exception $e) {
                    $user->update(['billing_address_syn' => false]);
                    Notification::create([
                        'user_id' => $user->id,
                        'type' => 'billing_address_syn',
                        'title' => 'Billing Address Sync Failed',
                        'message' => "Failed to sync billing address with ChargeBee for user ID {$user->id}",
                        'data' => [
                            'error_message' => $e->getMessage(),
                            'ip_address' => request()->ip()
                        ]
                    ]);
                    // Log general error but don't fail the whole request
                    Log::error('ChargeBee customer billing address update failed: ' . $e->getMessage(), [
                        'user_id' => $user->id,
                        'chargebee_customer_id' => $chargebee_customer_id
                    ]);
                }
            } else {
                // If no ChargeBee customer exists, create one
                try {
                    // Structure according to ChargeBee API documentation
                    // Convert country name to 2-letter country code as required by ChargeBee
                    $countryCode = $this->shortName($request->modalcountry);
                    
                    $customerData = [
                        "first_name" => $user->name,
                        "email" => $user->email,
                        "phone" => $user->phone,
                        "billing_address" => [
                            "first_name" => $user->name,
                            "company" => $request->modalAddressCompany ?? "",
                            "line1" => $request->modalAddressAddress1,
                            "line2" => $request->modalAddressAddress2 ?? "",
                            "city" => $request->modalAddressCity,
                            "state" => $request->modalAddressState ?? "",
                            "zip" => $request->modalAddressZipCode,
                            "country" => $countryCode
                        ]
                    ];
                    
                    // Log the customer data being sent to ChargeBee
                    // Log::info("Creating new ChargeBee customer with data", [
                    //     'customer_data' => $customerData
                    // ]);
                    
                    $result = Customer::create($customerData);
                    
                    if ($result && $result->customer()) {
                        $customer = $result->customer();
                        $customerId = $customer->id;
                        $customerValues = $customer->getValues();
                        // $customerValues['billing_address'] ?? null is null then create flag for billing address not saved
                        if(!$customerValues['billing_address']) {
                            Log::info("ChargeBee customer billing address not saved", [
                                'user_id' => $user->id,
                                'chargebee_customer_id' => $customerId,
                                'customer_data' => $customerValues
                            ]);
                            $user->update(['billing_address_syn' => false]);
                        }else{
                            Log::info("ChargeBee customer billing address saved", [
                                'user_id' => $user->id,
                                'chargebee_customer_id' => $customerId,
                                'customer_data' => $customerValues
                            ]);
                            $user->update(['billing_address_syn' => true]);
                        }
                        // Save the new ChargeBee customer ID to the user
                        $user->update(['chargebee_customer_id' => $customerId]);
                        
                        // Log with detailed billing address information
                        
                        Log::info("New ChargeBee customer created with billing address", [
                            'user_id' => $user->id,
                            'chargebee_customer_id' => $customerId,
                            'customer_data' => $customerValues,
                            'billing_address' => $customerValues['billing_address'] ?? null
                        ]);
                    }
                } catch (\ChargeBee\ChargeBee\Exceptions\APIError $e) {
                    $user->update(['billing_address_syn' => false]);
                    Notification::create([
                        'user_id' => $user->id,
                        'type' => 'billing_address_syn',
                        'title' => 'Billing Address Sync Failed',
                        'message' => "Failed to sync billing address with ChargeBee for user ID {$user->id}",
                        'data' => [
                            'error_message' => $e->getMessage(),
                            'ip_address' => request()->ip()
                        ]
                    ]);
                    // Specifically handle ChargeBee API errors
                    Log::error('ChargeBee API Error creating customer: ' . $e->getMessage(), [
                        'user_id' => $user->id,
                        'api_error_code' => $e->getApiErrorCode(),
                        'http_status_code' => $e->getHttpStatusCode()
                    ]);
                } catch (\Exception $e) {
                    $user->update(['billing_address_syn' => false]);
                    // billing_address_syn failed
                    Notification::create([
                        'user_id' => $user->id,
                        'type' => 'billing_address_syn',
                        'title' => 'Billing Address Sync Failed',
                        'message' => "Failed to sync billing address with ChargeBee for user ID {$user->id}",
                        'data' => [
                            'error_message' => $e->getMessage(),
                            'ip_address' => request()->ip()
                        ]
                    ]);
                    // Log ChargeBee creation error but don't fail the whole request
                    Log::error('ChargeBee customer creation failed: ' . $e->getMessage(), [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Add debug information to help with troubleshooting if needed
            $debugInfo = [];
            if (config('app.debug')) {
                $debugInfo = [
                    'chargebee_customer_id' => $chargebee_customer_id,
                    'user_id' => $user->id
                ];
            }
            
            return response()->json(array_merge([
                "billing_address_syn" => $user->billing_address_syn,
                'success' => true,
                'message' => 'Billing address updated successfully in both local database and payment gateway'
            ], $debugInfo));
        } catch (\ChargeBee\ChargeBee\Exceptions\APIError $e) {
            // Handle API errors from ChargeBee
            Log::error('ChargeBee API Error in address update process: ' . $e->getMessage(), [
                'user_id' => auth()->id() ?? 'unknown',
                'api_error_code' => $e->getApiErrorCode(),
                'http_status_code' => $e->getHttpStatusCode()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error updating billing address with payment gateway: ' . $e->getMessage(),
                'error_code' => $e->getApiErrorCode()
            ], 500);
        } catch (\Exception $e) {
            Log::error('Error updating billing address: ' . $e->getMessage(), [
                'user_id' => auth()->id() ?? 'unknown'
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error updating billing address: ' . $e->getMessage()
            ], 500);
        }
    }
    // shortName function to convert country name to short name
    public function shortName($country)
    {
        $countries = [
            'Afghanistan' => 'AF',
            'Albania' => 'AL',
            'Algeria' => 'DZ',
            'Andorra' => 'AD',
            'Angola' => 'AO',
            'Antigua and Barbuda' => 'AG',
            'Argentina' => 'AR',
            'Armenia' => 'AM',
            'Australia' => 'AU',
            'Austria' => 'AT',
            'Azerbaijan' => 'AZ',
            'Bahamas' => 'BS',
            'Bahrain' => 'BH',
            'Bangladesh' => 'BD',
            'Barbados' => 'BB',
            'Belarus' => 'BY',
            'Belgium' => 'BE',
            'Belize' => 'BZ',
            'Benin' => 'BJ',
            'Bhutan' => 'BT',
            'Bolivia' => 'BO',
            'Bosnia and Herzegovina' => 'BA',
            'Botswana' => 'BW',
            'Brazil' => 'BR',
            'Brunei' => 'BN',
            'Bulgaria' => 'BG',
            'Burkina Faso' => 'BF',
            'Burundi' => 'BI',
            'Cabo Verde' => 'CV',
            'Cambodia' => 'KH',
            'Cameroon' => 'CM',
            'Canada' => 'CA',
            'Central African Republic' => 'CF',
            'Chad' => 'TD',
            'Chile' => 'CL',
            'China' => 'CN',
            'Colombia' => 'CO',
            'Comoros' => 'KM',
            'Congo (Congo-Brazzaville)' => 'CG',
            'Costa Rica' => 'CR',
            'Croatia' => 'HR',
            'Cuba' => 'CU',
            'Cyprus' => 'CY',
            'Czech Republic' => 'CZ',
            'Democratic Republic of the Congo' => 'CD',
            'Denmark' => 'DK',
            'Djibouti' => 'DJ',
            'Dominica' => 'DM',
            'Dominican Republic' => 'DO',
            'Ecuador' => 'EC',
            'Egypt' => 'EG',
            'El Salvador' => 'SV',
            'Equatorial Guinea' => 'GQ',
            'Eritrea' => 'ER',
            'Estonia' => 'EE',
            'Eswatini' => 'SZ',
            'Ethiopia' => 'ET',
            'Fiji' => 'FJ',
            'Finland' => 'FI',
            'France' => 'FR',
            'Gabon' => 'GA',
            'Gambia' => 'GM',
            'Georgia' => 'GE',
            'Germany' => 'DE',
            'Ghana' => 'GH',
            'Greece' => 'GR',
            'Grenada' => 'GD',
            'Guatemala' => 'GT',
            'Guinea' => 'GN',
            'Guinea-Bissau' => 'GW',
            'Guyana' => 'GY',
            'Haiti' => 'HT',
            'Honduras' => 'HN',
            'Hungary' => 'HU',
            'Iceland' => 'IS',
            'India' => 'IN',
            'Indonesia' => 'ID',
            'Iran' => 'IR',
            'Iraq' => 'IQ',
            'Ireland' => 'IE',
            'Israel' => 'IL',
            'Italy' => 'IT',
            'Ivory Coast' => 'CI',
            'Jamaica' => 'JM',
            'Japan' => 'JP',
            'Jordan' => 'JO',
            'Kazakhstan' => 'KZ',
            'Kenya' => 'KE',
            'Kiribati' => 'KI',
            'Kuwait' => 'KW',
            'Kyrgyzstan' => 'KG',
            'Laos' => 'LA',
            'Latvia' => 'LV',
            'Lebanon' => 'LB',
            'Lesotho' => 'LS',
            'Liberia' => 'LR',
            'Libya' => 'LY',
            'Liechtenstein' => 'LI',
            'Lithuania' => 'LT',
            'Luxembourg' => 'LU',
            'Madagascar' => 'MG',
            'Malawi' => 'MW',
            'Malaysia' => 'MY',
            'Maldives' => 'MV',
            'Mali' => 'ML',
            'Malta' => 'MT',
            'Marshall Islands' => 'MH',
            'Mauritania' => 'MR',
            'Mauritius' => 'MU',
            'Mexico' => 'MX',
            'Micronesia' => 'FM',
            'Moldova' => 'MD',
            'Monaco' => 'MC',
            'Mongolia' => 'MN',
            'Montenegro' => 'ME',
            'Morocco' => 'MA',
            'Mozambique' => 'MZ',
            'Myanmar' => 'MM',
            'Namibia' => 'NA',
            'Nauru' => 'NR',
            'Nepal' => 'NP',
            'Netherlands' => 'NL',
            'New Zealand' => 'NZ',
            'Nicaragua' => 'NI',
            'Niger' => 'NE',
            'Nigeria' => 'NG',
            'North Korea' => 'KP',
            'North Macedonia' => 'MK',
            'Norway' => 'NO',
            'Oman' => 'OM',
            'Pakistan' => 'PK',
            'Palau' => 'PW',
            'Palestine' => 'PS',
            'Panama' => 'PA',
            'Papua New Guinea' => 'PG',
            'Paraguay' => 'PY',
            'Peru' => 'PE',
            'Philippines' => 'PH',
            'Poland' => 'PL',
            'Portugal' => 'PT',
            'Qatar' => 'QA',
            'Romania' => 'RO',
            'Russia' => 'RU',
            'Rwanda' => 'RW',
            'Saint Kitts and Nevis' => 'KN',
            'Saint Lucia' => 'LC',
            'Saint Vincent and the Grenadines' => 'VC',
            'Samoa' => 'WS',
            'San Marino' => 'SM',
            'Sao Tome and Principe' => 'ST',
            'Saudi Arabia' => 'SA',
            'Senegal' => 'SN',
            'Serbia' => 'RS',
            'Seychelles' => 'SC',
            'Sierra Leone' => 'SL',
            'Singapore' => 'SG',
            'Slovakia' => 'SK',
            'Slovenia' => 'SI',
            'Solomon Islands' => 'SB',
            'Somalia' => 'SO',
            'South Africa' => 'ZA',
            'South Korea' => 'KR',
            'South Sudan' => 'SS',
            'Spain' => 'ES',
            'Sri Lanka' => 'LK',
            'Sudan' => 'SD',
            'Suriname' => 'SR',
            'Sweden' => 'SE',
            'Switzerland' => 'CH',
            'Syria' => 'SY',
            'Taiwan' => 'TW',
            'Tajikistan' => 'TJ',
            'Tanzania' => 'TZ',
            'Thailand' => 'TH',
            'Timor-Leste' => 'TL',
            'Togo' => 'TG',
            'Tonga' => 'TO',
            'Trinidad and Tobago' => 'TT',
            'Tunisia' => 'TN',
            'Turkey' => 'TR',
            'Turkmenistan' => 'TM',
            'Tuvalu' => 'TV',
            'Uganda' => 'UG',
            'Ukraine' => 'UA',
            'United Arab Emirates' => 'AE',
            'United Kingdom' => 'GB',
            'United States' => 'US',
            'Uruguay' => 'UY',
            'Uzbekistan' => 'UZ',
            'Vanuatu' => 'VU',
            'Vatican City' => 'VA',
            'Venezuela' => 'VE',
            'Vietnam' => 'VN',
            'Yemen' => 'YE',
            'Zambia' => 'ZM',
            'Zimbabwe' => 'ZW'
        ];

        // Return the country code if found, otherwise return the original country name
        // This fallback ensures ChargeBee still gets a value even if the exact mapping isn't found
        return $countries[$country] ?? $country;
    }
}