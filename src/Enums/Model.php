<?php

declare(strict_types=1);

namespace GenerativeAI\Enums;

enum Model: string
{
    case Default = 'models/text-bison-001';
    case GeminiPro = 'models/gemini-pro';
    case GeminiProVision = 'models/gemini-pro-vision';
    case Embedding = 'models/embedding-001';
    case AQA = 'models/aqa';
}
