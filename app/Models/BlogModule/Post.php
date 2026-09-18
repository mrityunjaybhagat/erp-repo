<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'slug', 'excerpt', 'content', 'category_id', 'status', 'featured_image', 'published_at'];

    protected $casts = ['published_at' => 'datetime'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
