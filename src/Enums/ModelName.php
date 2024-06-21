<?php

declare(strict_types=1);

namespace GeminiAPI\Enums;

enum ModelName: string
{
    case Default = 'models/text-bison-001';
    case GeminiPro = 'models/gemini-pro';
    case GeminiFlash = 'models/gemini-1.5-flash';
    case GeminiProVision = 'models/gemini-pro-vision';
    case Embedding = 'models/embedding-001';
    case AQA = 'models/aqa';
}
