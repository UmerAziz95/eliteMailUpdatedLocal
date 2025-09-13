<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketsImapEmails extends Model
{
    protected $fillable = [
        'message_id', 'folder', 'subject', 'from', 'to', 'cc', 'bcc', 'body', 'date'
    ];
}
