<?php

declare(strict_types=1);

namespace GenerativeAI\Responses;

use GenerativeAI\Traits\ArrayTypeValidator;
use GenerativeAI\Resources\Candidate;
use GenerativeAI\Resources\Parts\PartInterface;
use GenerativeAI\Resources\Parts\TextPart;
use GenerativeAI\Resources\PromptFeedback;
use InvalidArgumentException;
use ValueError;

class GenerateContentResponse
{
    use ArrayTypeValidator;

    /**
     * @param Candidate[] $candidates
     * @param PromptFeedback $promptFeedback
     */
    public function __construct(
        public readonly array $candidates,
        public readonly PromptFeedback $promptFeedback,
    ) {
        $this->ensureArrayOfType($candidates, Candidate::class);
    }

    /**
     * @return PartInterface[]
     */
    public function parts(): array
    {
        if (empty($this->candidates)) {
            throw new ValueError(
                'The `GenerateContentResponse::parts()` quick accessor '.
                'only works for a single candidate, but none were returned. '.
                'Check the `GenerateContentResponse::$promptFeedback` to see if the prompt was blocked.'
            );
        }

        if (count($this->candidates) > 1) {
            throw new ValueError(
                'The `GenerateContentResponse::parts()` quick accessor '.
                'only works with a single candidate. '.
                'With multiple candidates use GenerateContentResponse.candidates[index].text'
            );
        }

        return $this->candidates[0]->content->parts;
    }

    public function text(): string
    {
        $parts = $this->parts();

        if (count($parts) > 1 || !$parts[0] instanceof TextPart) {
            throw new ValueError(
                'The `GenerateContentResponse::text()` quick accessor '.
                'only works for simple (single-`Part`) text responses. '.
                'This response contains multiple `Parts`. '.
                'Use the `GenerateContentResponse::parts()` accessor '.
                'or the full `GenerateContentResponse.candidates[index].content.parts` lookup instead'
            );
        }

        return $parts[0]->text;
    }

    /**
     * @param array{
     *  promptFeedback: array{
     *   blockReason: string|null,
     *   safetyRatings: array<int, array{category: string, probability: string, blocked: bool|null}>,
     *  },
     *  candidates: array<int, array{
     *   citationMetadata: array<string, mixed>,
     *   safetyRatings: array<int, array{category: string, probability: string, blocked: bool|null}>,
     *   content: array{parts: array<int, array{text: string, inlineData: array{mimeType: string, data: string}}>, role: string},
     *   finishReason: string,
     *   tokenCount: int,
     *   index: int
     *  }>,
     * } $array
     * @return self
     */
    public static function fromArray(array $array): self
    {
        if (empty($array['promptFeedback']) || !is_array($array['promptFeedback'])) {
            throw new InvalidArgumentException('invalid promptFeedback');
        }

        $candidates = array_map(
            static fn (array $candidate): Candidate => Candidate::fromArray($candidate),
            $array['candidates'] ?? [],
        );

        $promptFeedback = PromptFeedback::fromArray($array['promptFeedback']);

        return new self($candidates, $promptFeedback);
    }
}