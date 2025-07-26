<?php

namespace Modules\CustomPrompt\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Domain;

class CustomPrompt extends Model
{
    use HasFactory;

    protected $fillable = [
        'domain_id', 'title', 'description', 'icon', 'allow_btn_text', 'allow_btn_color',
        'allow_btn_text_color', 'deny_btn_text', 'deny_btn_color', 'deny_btn_text_color',
        'enable_desktop', 'enable_mobile', 'delay', 'reappear', 'status'
    ];

    public function domain()
    {
        return $this->belongsTo(Domain::class);
    }
}
