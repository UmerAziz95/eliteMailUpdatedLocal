<?php

namespace App\Services;

use Webklex\IMAP\Facades\Client;
use App\Models\TicketsImapEmails as Email;
use Illuminate\Support\Facades\Log;

class EmailImapService
{
    public function fetchAndStoreEmails()
    {
        Log::info("📬 Starting IMAP email fetch...");

        $client = Client::account('default'); 

        try {
            $client->connect();
            Log::info("✅ Connected to IMAP server.");
        } catch (\Exception $e) {
            Log::error("❌ IMAP Connection failed: " . $e->getMessage());
            return;
        }

        $folders = $client->getFolders();
        Log::info("📂 Found folders: " . implode(', ', array_map(fn($f) => $f->name, $folders->all())));

        foreach ($folders as $folder) {
            $fname = strtolower($folder->name);

            if (!in_array($fname, ['inbox', 'sent', 'sent items'])) {
                Log::info("⏭️ Skipping folder: {$folder->name}");
                continue;
            }

            Log::info("➡️ Processing folder: {$folder->name}");

            try {
                $messages = $folder->messages()->all()->fetchOrderDesc()->limit(50)->get();
                Log::info("📧 Found " . $messages->count() . " messages in {$folder->name}");

                foreach ($messages as $message) {
                    $msgId   = $message->getMessageId();
                    $subject = $message->getSubject();

                    Log::info("🔍 Message: {$msgId} | Subject: {$subject}");
                    Log::info("📤 RAW From: ", $message->getFrom() ? $message->getFrom()->toArray() : []);
                    Log::info("📥 RAW To: ", $message->getTo() ? $message->getTo()->toArray() : []);

                    $from = $this->extractAddresses($message->getFrom());
                    $to   = $this->extractAddresses($message->getTo());
                    $cc   = $this->extractAddresses($message->getCc());
                    $bcc  = $this->extractAddresses($message->getBcc());

                    Log::info("✅ Extracted From: {$from}");
                    Log::info("✅ Extracted To: {$to}");
                     Log::info("📧 Message: ".$message->getMessageId()." | Subject: ".$message->getSubject());

    // Dump all headers for debugging
Log::info("🔍 Message: {$msgId} | Subject: {$subject}");

// ✅ FIXED: always cast to JSON string
Log::info("📤 RAW From: " . json_encode($message->getFrom()?->toArray() ?? []));
Log::info("📥 RAW To: " . json_encode($message->getTo()?->toArray() ?? []));
Log::info("📜 Full Headers: " . json_encode($message->getHeaders()?->toArray() ?? []));

$from = $this->extractAddresses($message->getFrom());
$to   = $this->extractAddresses($message->getTo());
$cc   = $this->extractAddresses($message->getCc());
$bcc  = $this->extractAddresses($message->getBcc());

Log::info("✅ Extracted From: {$from}");
Log::info("✅ Extracted To: {$to}");



                    Email::updateOrCreate(
                        ['message_id' => $msgId],
                        [
                            'folder'  => $folder->name,
                            'subject' => $subject,
                            'from'    => $from,
                            'to'      => $to,
                            'cc'      => $cc,
                            'bcc'     => $bcc,
                            'body'    => $message->getHTMLBody() ?: $message->getTextBody(),
                            'date'    => $message->getDate(),
                        ]
                    );
                }
            } catch (\Exception $e) {
                Log::error("❌ Error processing folder {$folder->name}: " . $e->getMessage());
            }
        }

        $client->disconnect();
        Log::info("🔌 Disconnected from IMAP server.");
    }

    /**
     * Normalize address extraction
     */
   private function extractAddresses($addresses)
{
    if (!$addresses) {
        return null;
    }

    $list = [];
    // Webklex AddressCollection has ->all() method
    if (is_object($addresses) && method_exists($addresses, 'all')) {
        $addresses = $addresses->all();
    }

    foreach ($addresses as $addr) {
        if (is_object($addr)) {
            // Webklex Address object has 'mail' property
            if (property_exists($addr, 'mail')) {
                $list[] = $addr->mail;
            }
            // Fallback: try 'address' property (older version)
            elseif (property_exists($addr, 'address')) {
                $list[] = $addr->address;
            }
            // Fallback: try __toString()
            elseif (method_exists($addr, '__toString')) {
                $list[] = (string) $addr;
            }
        } elseif (is_string($addr)) {
            $list[] = $addr;
        } elseif (is_array($addr) && isset($addr['mail'])) {
            $list[] = $addr['mail'];
        }
    }

    return implode(',', $list);
}
}
