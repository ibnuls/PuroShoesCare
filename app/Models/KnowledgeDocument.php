<?php

namespace App\Models;

class KnowledgeDocument extends Record
{
    protected $table = 'knowledge_documents';

    public $timestamps = true;

    public function chunks()
    {
        return $this->hasMany(KnowledgeChunk::class, 'document_id')->orderBy('position');
    }

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }
}
