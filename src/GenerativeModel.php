<?php

declare(strict_types=1);

namespace GeminiAPI;

use BadMethodCallException;
use CurlHandle;
use GeminiAPI\Enums\ModelName;
use GeminiAPI\Enums\Role;
use GeminiAPI\Requests\CountTokensRequest;
use GeminiAPI\Requests\GenerateContentRequest;
use GeminiAPI\Requests\GenerateContentStreamRequest;
use GeminiAPI\Responses\CountTokensResponse;
use GeminiAPI\Responses\GenerateContentResponse;
use GeminiAPI\Resources\Content;
use GeminiAPI\Resources\Parts\PartInterface;
use GeminiAPI\Traits\ArrayTypeValidator;
use Psr\Http\Client\ClientExceptionInterface;

class GenerativeModel
{
    use ArrayTypeValidator;

    /** @var SafetySetting[] */
    private array $safetySettings = [];

    private ?GenerationConfig $generationConfig = null;

    private array $systemInstructions = []; // Added property for system instructions

    public function __construct(
        private readonly Client $client,
        public readonly ModelName $modelName,
    ) {
    }

    /**
     * Adds a safety setting to the configuration.
     * @param SafetySetting $safetySetting
     * @return self
     */
    public function withAddedSafetySetting(SafetySetting $safetySetting): self
    {
        $clone = clone $this;
        $clone->safetySettings[] = $safetySetting;
        return $clone;
    }

    /**
     * Configures generation settings.
     * @param GenerationConfig $generationConfig
     * @return self
     */
    public function withGenerationConfig(GenerationConfig $generationConfig): self
    {
        $clone = clone $this;
        $clone->generationConfig = $generationConfig;
        return $clone;
    }

    /**
     * Adds system instructions to be sent with the request.
     * @param array $instructions
     * @return self
     */
    public function withSystemInstructions(array $instructions): self
    {
        $clone = clone $this;
        $clone->systemInstructions = $instructions;
        return $clone;
    }

    /**
     * Generates content based on parts.
     * @param PartInterface ...$parts
     * @return GenerateContentResponse
     * @throws ClientExceptionInterface
     */
    public function generateContent(PartInterface ...$parts): GenerateContentResponse
    {
        $content = new Content($parts, Role::User);

        return $this->generateContentWithContents([$content]);
    }

    /**
     * Generates content with detailed contents.
     * @param Content[] $contents
     * @return GenerateContentResponse
     * @throws ClientExceptionInterface
     */
    public function generateContentWithContents(array $contents): GenerateContentResponse
    {
        $this->ensureArrayOfType($contents, Content::class);

        $request = new GenerateContentRequest(
            $this->modelName,
            $contents,
            $this->safetySettings,
            $this->generationConfig,
            $this->systemInstructions  // Include system instructions in the request
        );

        return $this->client->generateContent($request);
    }

    public function startChat(): ChatSession
    {
        return new ChatSession($this);
    }
}
