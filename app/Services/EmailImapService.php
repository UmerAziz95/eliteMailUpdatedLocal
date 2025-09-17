<?php

namespace App\Services;

use Webklex\IMAP\Facades\Client;
use App\Models\TicketsImapEmails as Email;
use Illuminate\Support\Facades\Log;

class EmailImapService
{
    public function fetchAndStoreEmails()
    {
        $client = Client::account('default');

        try {
            $client->connect();
        } catch (\Exception $e) {
            Log::error("❌ IMAP Connection failed: " . $e->getMessage());
            return;
        }

        $folders = $client->getFolders();

        foreach ($folders as $folder) {
            $fname = strtolower($folder->name);

            if (!in_array($fname, ['inbox', 'sent', 'sent items'])) {
                continue;
            }

            try {
                $lastMessageId = Email::where('folder', $folder->name)
                    ->orderByDesc('date')
                    ->value('message_id');

                $query = $folder->messages()->all()->fetchOrderDesc()->limit(100);

                $messages = $query->get();

                $newMessages = collect();
                foreach ($messages as $message) {
                    $msgId = $message->getMessageId();
                    if ($msgId === $lastMessageId) break;
                    $newMessages->push($message);
                }

                foreach ($newMessages as $message) {
                    $msgId   = $message->getMessageId();
                    $subject = $message->getSubject();

                    $from = $this->extractAddresses($message->getFrom());
                    $to   = $this->extractAddresses($message->getTo());
                    $cc   = $this->extractAddresses($message->getCc());
                    $bcc  = $this->extractAddresses($message->getBcc());

                    $isSent = in_array($fname, ['sent', 'sent items']);
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
                            'is_sent' => $isSent,
                        ]
                    );
                }
            } catch (\Exception $e) {
                Log::error("❌ Error processing folder {$folder->name}: " . $e->getMessage());
            }
        }

        $client->disconnect();
    }

    private function extractAddresses($addresses)
    {
        if (!$addresses) {
            return null;
        }
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

 private function extractLatestReply($html)
{
    // 1. Gmail: Remove everything from the first gmail_quote onward
    $html = preg_replace('/<div class="gmail_quote.*$/is', '', $html);
    $html = preg_replace('/<blockquote class="gmail_quote.*?<\/blockquote>/is', '', $html);
    $html = preg_replace('/<div class="gmail_attr".*?<\/div>/is', '', $html);

    // 2. Outlook/Office365: Remove everything after the first <hr> or border-top divider
    $html = preg_replace('/<hr[^>]*>.*$/is', '', $html);
    $html = preg_replace('/<div[^>]+border-top:[^>]+>.*$/is', '', $html);

    // 3. Outlook: Remove everything after the first blockquote
    $html = preg_replace('/<blockquote.*$/is', '', $html);

    // 4. Remove html, head, and body tags and keep only the inside content
    if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $html, $matches)) {
        $html = $matches[1];
    }
    $html = preg_replace('/<\/?(html|head)[^>]*>/i', '', $html);

    // 5. Optionally, remove only empty <div> and <p> at the ends
    $html = preg_replace('/(<p[^>]*>(&nbsp;|\s|<br\s*\/?>)*<\/p>)+$/is', '', $html);
    $html = preg_replace('/(<div[^>]*>(&nbsp;|\s|<br\s*\/?>)*<\/div>)+$/is', '', $html);
    $html = preg_replace('/(<br\s*\/?>\s*)+$/is', '', $html);

    // 6. Trim whitespace
    return trim($html);
}
}