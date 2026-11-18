<?php

namespace App\Models;

class KnowledgeChunk extends Record
{
    protected $table = 'knowledge_chunks';

    public $timestamps = true;

    public function document()
    {
        return $this->belongsTo(KnowledgeDocument::class, 'document_id');
    }

    protected function casts(): array
    {
        return ['embedding' => 'array'];
    }
}
