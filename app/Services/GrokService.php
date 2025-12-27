<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class GrokService
{
    private string $baseUrl;
    private string $apiKey;
    private string $model = 'grok-4-latest';
    private int $timeout;
    private int $connectTimeout;

    public function __construct()
    {
        $this->apiKey = config('services.grok.api_key');
        $this->baseUrl = config('services.grok.base_url', 'https://api.x.ai/v1');
        $this->timeout = config('services.grok.timeout', 120);
        $this->connectTimeout = config('services.grok.connect_timeout', 30);

        if (empty($this->apiKey)) {
            throw new Exception('Grok API key not configured');
        }

        Log::info('GrokService initialized', [
            'timeout' => $this->timeout,
            'connect_timeout' => $this->connectTimeout,
            'base_url' => $this->baseUrl
        ]);
    }

    public function analyzeFeedback(array $feedbacks): array
    {
        try {
            Log::info('Starting Grok summarization', ['feedback_count' => count($feedbacks)]);

            if (empty($feedbacks)) {
                return [
                    'success' => false,
                    'error' => 'No feedbacks provided',
                    'results' => null
                ];
            }

            $feedbackText = $this->prepareFeedbackText($feedbacks);

            $prompt = $this->createSummarizationPrompt($feedbackText, count($feedbacks));

            // Call Grok API
            $response = $this->callGrokAPI($prompt);

            return [
                'success' => true,
                'results' => [
                    'summary' => $response['content'] ?? null,
                    'model' => $this->model,
                    'feedback_count' => count($feedbacks),
                    'usage' => $response['usage'] ?? null
                ],
                'error' => null
            ];
        } catch (Exception $e) {
            Log::error('Grok summarization failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'results' => null,
                'error' => 'Grok API error: ' . $e->getMessage()
            ];
        }
    }

    private function prepareFeedbackText(array $feedbacks): string
    {
        $preparedText = '';

        foreach ($feedbacks as $index => $feedback) {
            if (is_array($feedback)) {
                $text = $feedback['text'] ?? $feedback['feedback'] ?? $feedback['content'] ?? implode(' ', $feedback);
            } else {
                $text = (string)$feedback;
            }

            $preparedText .= "Feedback " . ($index + 1) . ": " . $text . "\n\n";
        }

        return trim($preparedText);
    }

    private function createSummarizationPrompt(string $feedbackText, int $feedbackCount): string
    {
        return "You are analyzing {$feedbackCount} student feedback comments that may contain English, Tagalog, and Bicol languages.

            FEEDBACKS TO ANALYZE:
            {$feedbackText}

            Perform the following analysis:

            1. TOPIC MODELING: Identify 3-5 main topics from the feedback. For each topic include:
               - topic_name: Clear topic label
               - keyphrases: Array of 5-8 key phrases representing the topic
               - sentiment: Overall sentiment for this topic (positive/negative/neutral/mixed)
               - summary: Brief summary of what students are saying about this topic
               - frequency: Percentage representation of how frequently this topic appears (0-100)

            2. OVERALL SENTIMENT: Provide overall sentiment analysis including:
               - overall_sentiment: dominant sentiment across all feedback (Positive, Negative, Mixed, Neutral)
               - sentiment_score: numerical score from -1 (very negative) to +1 (very positive)
               - positive_aspects: array of positive themes
               - negative_aspects: array of negative themes
               - neutral_aspects: array of neutral observations

            3. KEYPHRASE EXTRACTION: Extract important keyphrases across all feedback with:
               - phrase: the keyphrase
               - frequency: how many times it appears
               - sentiment: associated sentiment
            Present as a list, sorted by frequency descending.

            4. COMPREHENSIVE SUMMARY: Provide an executive summary of all feedback.


            Also detect and note languages used.

            Output ONLY a valid JSON object with no additional text or explanations. The JSON structure must be:

            {
                \"topic_modeling\": [
                    {
                        \"topic_name\": \"string\",
                        \"keyphrases\": [\"string\", ...],
                        \"sentiment\": \"string\",
                        \"summary\": \"string\",
                        \"frequency\": number
                    },
                    ...
                ],
                \"overall_sentiment\": {
                    \"overall_sentiment\": \"string\",
                    \"sentiment_score\": number,
                    \"positive_aspects\": [\"string\", ...],
                    \"negative_aspects\": [\"string\", ...],
                    \"neutral_aspects\": [\"string\", ...]
            },
            \"keyphrase_extraction\": [
                    {
                        \"phrase\": \"string\",
                        \"frequency\": integer,
                        \"sentiment\": \"string\"
                    },
                    ...
                ],
                \"comprehensive_summary\": \"string\",
                \"language_notes\": \"string\"
            }
            
            Focus on understanding the meaning regardless of language mixing.";
    }

    private function callGrokAPI(string $prompt): array
    {
        Log::info('Calling Grok API', [
            'timeout' => $this->timeout,
            'connect_timeout' => $this->connectTimeout,
            'prompt_length' => strlen($prompt)
        ]);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])
            ->timeout($this->timeout)
            ->connectTimeout($this->connectTimeout)
            ->retry(3, 500)
            ->post($this->baseUrl . '/chat/completions', [
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert at analyzing student feedback from multiple languages including English, Tagalog, and Bicol. Provide clear, structured summaries with actionable insights.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'model' => $this->model,
                'stream' => false,
                'temperature' => 0.3,
                'max_tokens' => 2000
            ]);

        if (!$response->successful()) {
            throw new Exception('Grok API request failed: ' . $response->body());
        }

        $data = $response->json();

        Log::info('Grok API response', [
            'usage' => $data['usage'] ?? null,
            'model' => $data['model'] ?? null
        ]);

        return [
            'content' => $data['choices'][0]['message']['content'] ?? null,
            'usage' => $data['usage'] ?? null,
            'model' => $data['model'] ?? null
        ];
    }

    public function testConnection(): array
    {
        try {
            $response = $this->callGrokAPI("Just say 'Connection test successful' and nothing else.");
            
            return [
                'success' => true,
                'message' => 'Grok API connection successful',
                'response' => $response['content'] ?? null
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
