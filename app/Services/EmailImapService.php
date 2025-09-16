<?php

namespace App\Services;

use Webklex\IMAP\Facades\Client;
use App\Models\TicketsImapEmails as Email;
use Illuminate\Support\Facades\Log;

class EmailImapService
{
    public function fetchAndStoreEmails()
    {
        // Log::info("📬 Starting IMAP email fetch...");

        $client = Client::account('default');

        try {
            $client->connect();
            // Log::info("✅ Connected to IMAP server.");
        } catch (\Exception $e) {
            Log::error("❌ IMAP Connection failed: " . $e->getMessage());
            return;
        }

        $folders = $client->getFolders();
        // Log::info("📂 Found folders: " . implode(', ', array_map(fn($f) => $f->name, $folders->all())));

        foreach ($folders as $folder) {
            $fname = strtolower($folder->name);

            if (!in_array($fname, ['inbox', 'sent', 'sent items'])) {
                Log::info("⏭️ Skipping folder: {$folder->name}");
                continue;
            }

            // Log::info("➡️ Processing folder: {$folder->name}");

            try {
                // --- Efficient fetch: get the latest message_id for this folder from the DB ---
                $lastMessageId = Email::where('folder', $folder->name)
                    ->orderByDesc('date')
                    ->value('message_id');
                // Log::info("🔎 Last message_id for folder {$folder->name}: " . ($lastMessageId ?? 'none'));

                // Prepare the message query
                $query = $folder->messages()->all()->fetchOrderDesc()->limit(100);

                // If we have a last message_id, fetch only messages after that
                // Webklex IMAP doesn't support direct "after message_id", but we can filter after fetching
                $messages = $query->get();
                // Log::info("📧 Fetched " . $messages->count() . " messages from {$folder->name}");

                $newMessages = collect();
                $foundLast = false;
                foreach ($messages as $message) {
                    $msgId = $message->getMessageId();
                    if ($msgId === $lastMessageId) {
                        $foundLast = true;
                        break;
                    }
                    $newMessages->push($message);
                }
                // Log::info("🆕 New messages to store: " . $newMessages->count());

                foreach ($newMessages as $message) {
                    $msgId   = $message->getMessageId();
                    $subject = $message->getSubject();

                    // // Log structures for debugging
                    // Log::info("🔍 Message: {$msgId} | Subject: {$subject}");
                    // Log::info("📤 RAW From: " . json_encode($message->getFrom()?->toArray() ?? []));
                    // Log::info("📥 RAW To: " . json_encode($message->getTo()?->toArray() ?? []));
                    // Log::info("📜 Full Headers: " . json_encode($message->getHeaders()?->toArray() ?? []));

                    $from = $this->extractAddresses($message->getFrom());
                    $to   = $this->extractAddresses($message->getTo());
                    $cc   = $this->extractAddresses($message->getCc());
                    $bcc  = $this->extractAddresses($message->getBcc());

                    // Log::info("✅ Extracted From: {$from}");
                    // Log::info("✅ Extracted To: {$to}");

                    // Differentiation flag: is_sent
                    $isSent = in_array($fname, ['sent', 'sent items']);

                    // Clean the body to only save the latest reply, handling Gmail and Outlook formats
                    $htmlBody = $message->getHTMLBody();
                    $cleanBody = $htmlBody ? $this->extractLatestReply($htmlBody) : $message->getTextBody();

                    Email::updateOrCreate(
                        ['message_id' => $msgId],
                        [
                            'folder'  => $folder->name,
                            'subject' => $subject,
                            'from'    => $from,
                            'to'      => $to,
                            'cc'      => $cc,
                            'bcc'     => $bcc,
                            'body'    => $cleanBody,
                            'date'    => $message->getDate(),
                            'is_sent' => $isSent, // add this field to your migration/model!
                        ]
                    );
                }
            } catch (\Exception $e) {
                Log::error("❌ Error processing folder {$folder->name}: " . $e->getMessage());
            }
        }

        $client->disconnect();
        // Log::info("🔌 Disconnected from IMAP server.");
    }

    /**
     * Normalize address extraction
     */
    private function extractAddresses($addresses)
    {
        if (!$addresses) {
            return null;
        }

        // If it's a collection, get the array
        if (is_object($addresses) && method_exists($addresses, 'all')) {
            $addresses = $addresses->all();
        }

        $list = [];
        foreach ($addresses as $addr) {
            if (is_object($addr)) {
                if (property_exists($addr, 'mail')) {
                    $list[] = $addr->mail;
                } elseif (property_exists($addr, 'address')) {
                    $list[] = $addr->address;
                } elseif (method_exists($addr, '__toString')) {
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

    /**
     * Extract only the latest reply from an HTML email (removes quoted replies, Gmail and Outlook).
     */
    private function extractLatestReply($html)
    {
        // 1. Gmail: Remove everything from the first gmail_quote onward
        $html = preg_replace('/<div class="gmail_quote.*$/is', '', $html);
        $html = preg_replace('/<blockquote class="gmail_quote.*?<\/blockquote>/is', '', $html);
        $html = preg_replace('/<div class="gmail_attr".*?<\/div>/is', '', $html);

        // 2. Outlook/Office365: Remove everything after the first horizontal rule (<hr>) or border-top divider
        $html = preg_replace('/<hr[^>]*>.*$/is', '', $html);
        $html = preg_replace('/<div[^>]+border-top:[^>]+>.*$/is', '', $html);

        // 3. Outlook: Remove everything after the first blockquote (sometimes used for replies)
        $html = preg_replace('/<blockquote.*$/is', '', $html);

        // 4. Optionally, remove trailing empty <p>, <div>, and <br> tags
        $html = preg_replace('/(<p[^>]*>(&nbsp;|\s|<br\s*\/?>)*<\/p>)+$/is', '', $html);
        $html = preg_replace('/(<div[^>]*>(&nbsp;|\s|<br\s*\/?>)*<\/div>)+$/is', '', $html);
        $html = preg_replace('/(<br\s*\/?>\s*)+$/is', '', $html);

        // 5. Trim whitespace
        return trim($html);
    } 
}