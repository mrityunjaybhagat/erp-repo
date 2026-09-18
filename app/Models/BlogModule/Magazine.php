<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Magazine extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'slug', 'intro', 'cover_image', 'pdf_file', 'issue_month', 'issue_year', 'status', 'published_at'];

    protected $casts = ['published_at' => 'datetime'];
}
