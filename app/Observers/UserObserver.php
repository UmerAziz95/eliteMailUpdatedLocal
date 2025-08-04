<?php

namespace App\Observers;

use App\Models\User;
use App\Services\AccountCreationGHL;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    /**
     * GHL Service instance
     */
    protected AccountCreationGHL $ghlService;

    /**
     * Constructor
     */
    public function __construct(AccountCreationGHL $ghlService)
    {
        $this->ghlService = $ghlService;
    }

    /**
     * Handle the User "created" event.
     *
     * @param User $user
     * @return void
     */
    public function created(User $user): void
    {
        Log::info('UserObserver: User created event triggered', [
            'user_id' => $user->id,
            'email' => $user->email,
            'name' => $user->name
        ]);

        // Only create GHL contact if GHL integration is enabled
        if (config('services.ghl.enabled', false)) {
            try {
                $this->ghlService->createContact($user, 'lead');
                
                Log::info('UserObserver: GHL contact creation initiated', [
                    'user_id' => $user->id,
                    'email' => $user->email
                ]);
            } catch (\Exception $e) {
                Log::error('UserObserver: Failed to create GHL contact', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        } else {
            Log::info('UserObserver: GHL integration disabled, skipping contact creation', [
                'user_id' => $user->id
            ]);
        }
    }

    /**
     * Handle the User "updated" event.
     *
     * @param User $user
     * @return void
     */
    public function updated(User $user): void
    {
        // Only update GHL contact if integration is enabled and user has GHL contact ID
        if (config('services.ghl.enabled', false) && $user->ghl_contact_id) {
            try {
                $this->ghlService->updateContact($user, $user->ghl_contact_id);
                
                Log::info('UserObserver: GHL contact update initiated', [
                    'user_id' => $user->id,
                    'ghl_contact_id' => $user->ghl_contact_id
                ]);
            } catch (\Exception $e) {
                Log::error('UserObserver: Failed to update GHL contact', [
                    'user_id' => $user->id,
                    'ghl_contact_id' => $user->ghl_contact_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
    }

    /**
     * Handle the User "deleted" event.
     *
     * @param User $user
     * @return void
     */
    public function deleted(User $user): void
    {
        // Only delete GHL contact if integration is enabled and user has GHL contact ID
        if (config('services.ghl.enabled', false) && $user->ghl_contact_id) {
            try {
                $this->ghlService->deleteContact($user->ghl_contact_id);
                
                Log::info('UserObserver: GHL contact deletion initiated', [
                    'user_id' => $user->id,
                    'ghl_contact_id' => $user->ghl_contact_id
                ]);
            } catch (\Exception $e) {
                Log::error('UserObserver: Failed to delete GHL contact', [
                    'user_id' => $user->id,
                    'ghl_contact_id' => $user->ghl_contact_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
    }
}
