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
    private array $systemInstructions = [];

    private ?GenerationConfig $generationConfig = null;

    public function __construct(
        private readonly Client $client,
        public readonly ModelName $modelName,
    ) {
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
     * @throws ClientExceptionInterface
     */
    public function generateContent(PartInterface ...$parts): GenerateContentResponse
    {
        $content = new Content($parts, Role::User);

        return $this->generateContentWithContents([$content]);
    }

    /**
     * @param Content[] $contents
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
        );

        return $this->client->generateContent($request);
    }

    /**
     * @param callable(GenerateContentResponse): void $callback
     * @param PartInterface[] $parts
     * @param CurlHandle|null $ch
     * @return void
     */
    public function generateContentStream(
        callable $callback,
        array $parts,
        ?CurlHandle $ch = null,
    ): void {
        $this->ensureArrayOfType($parts, PartInterface::class);

        $content = new Content($parts, Role::User);

        $this->generateContentStreamWithContents($callback, [$content], $ch);
    }

    /**
     * @param callable(GenerateContentResponse): void $callback
     * @param Content[] $contents
     * @param CurlHandle|null $ch
     * @return void
     */
    public function generateContentStreamWithContents(
        callable $callback,
        array $contents,
        ?CurlHandle $ch = null,
    ): void {
        $this->ensureArrayOfType($contents, Content::class);

        $request = new GenerateContentStreamRequest(
            $this->modelName,
            $contents,
            $this->safetySettings,
            $this->generationConfig,
            $this->systemInstructions  // Passing the system instructions
        );


        $this->client->generateContentStream($request, $callback, $ch);
    }

    public function startChat(): ChatSession
    {
        return new ChatSession($this);
    }

    /**
     * @throws ClientExceptionInterface
     */
    public function countTokens(PartInterface ...$parts): CountTokensResponse
    {
        $content = new Content($parts, Role::User);
        $request = new CountTokensRequest(
            $this->modelName,
            [$content],
        );

        return $this->client->countTokens($request);
    }

    public function withAddedSafetySetting(SafetySetting $safetySetting): self
    {
        $clone = clone $this;
        $clone->safetySettings[] = $safetySetting;

        return $clone;
    }

    public function withGenerationConfig(GenerationConfig $generationConfig): self
    {
        $clone = clone $this;
        $clone->generationConfig = $generationConfig;

        return $clone;
    }
}
