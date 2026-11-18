<?php

return ['timezone' => 'Asia/Jakarta', 'demo' => (bool) env('PURO_DEMO_MODE', false), 'admin_whatsapp' => env('PURO_ADMIN_WHATSAPP', ''), 'gemini_key' => env('GEMINI_API_KEY', ''), 'chat_model' => env('GEMINI_MODEL', 'gemini-3.5-flash-lite'), 'embedding_model' => env('GEMINI_EMBEDDING_MODEL', 'gemini-embedding-001'), 'rag_min_similarity' => (float) env('RAG_MIN_SIMILARITY', 0.56), 'rag_top_k' => (int) env('RAG_TOP_K', 5), 'knowledge_directory' => storage_path('app/knowledge'), 'express_price' => 25000];
