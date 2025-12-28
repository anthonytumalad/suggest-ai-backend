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

        foreach ($feedbacks as $index => $item) {
            if (is_string($item)) {
                $decoded = json_decode($item, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $item = $decoded;
                } else {
                    $preparedText .= "Entry " . ($index + 1) . ": " . trim($item) . "\n\n";
                    continue;
                }
            }

            if (!is_array($item)) {
                $item = (array) $item;
            }

            $role = $item['role'] ?? 'unknown';
            $rating = $item['rating'] ?? null;
            $stars = $rating ? str_repeat('★', $rating) . str_repeat('☆', 5 - $rating) : 'No rating';
            $mainFeedback = trim($item['feedback'] ?? $item['message'] ?? $item['content'] ?? $item['text'] ?? '[No main feedback]');
            $suggestions = trim($item['suggestions'] ?? '');

            $entry = "Entry " . ($index + 1) . " [Role: {$role}, Rating: " . ($rating ?? 'N/A') . "/5 {$stars}]:\n";
            $entry .= "Main feedback: {$mainFeedback}\n";

            if ($suggestions !== '') {
                $entry .= "Suggestions: {$suggestions}\n";
            }

            $preparedText .= $entry . "\n";
        }

        return trim($preparedText);
    }

    private function createSummarizationPrompt(string $feedbackText, int $feedbackCount): string
    {
        return <<<PROMPT
            You are an expert analyst reviewing {$feedbackCount} student and staff feedback entries for an educational institution. The feedback may be written in English, Tagalog, Bicol, or a mix of these languages.

            Each feedback entry has:
            - A main comment ("feedback" field)
            - Optional suggestions for improvement ("suggestions" field)
            - A rating from 1 to 5 stars
            - A role: either "student", "staff", "teacher", and many more
            - May be anonymous or from a named sender

            FEEDBACKS TO ANALYZE:
            {$feedbackText}

            Analyze the feedback thoroughly and provide structured insights.

            Perform the following:

            1. TOPIC MODELING
            Identify 3-5 main topics discussed across all feedback.
            For each topic:
            - topic_name: Clear, concise label
            - keyphrases: 6-8 representative phrases or words
            - sentiment: positive / negative / neutral / mixed
            - average_rating: Average star rating for feedback mentioning this topic (1-5)
            - summary: Brief explanation of what people are saying
            - frequency: Approximate percentage of feedback related to this topic (0-100%)

            2. OVERALL SENTIMENT ANALYSIS
            - overall_sentiment: Dominant sentiment (Positive, Mostly Positive, Mixed, Mostly Negative, Negative)
            - sentiment_score: Weighted score from -1.0 (very negative) to +1.0 (very positive), considering both text and ratings
            - average_rating: Overall average star rating across all entries
            - rating_distribution: Percentage of feedback by star rating (e.g., 1★: 10%, 2★: 15%, etc.)
            - positive_aspects: List of commonly praised elements
            - negative_aspects: List of common complaints or concerns
            - suggestions_summary: Summary of recurring improvement suggestions

            3. KEYPHRASE EXTRACTION
            Extract the most frequent and meaningful phrases across main feedback and suggestions.
            Return as list sorted by frequency (highest first):
            - phrase: the keyphrase
            - frequency: approximate count
            - sentiment: positive / negative / neutral
            - sources: mainly from "feedback", "suggestions", or "both"

            4. ROLE-BASED INSIGHTS
            Highlight any notable differences between student and staff feedback.

            5. LANGUAGE NOTES
            Identify primary languages used and any notable code-switching patterns.

            6. COMPREHENSIVE EXECUTIVE SUMMARY
            A clear, actionable 3-5 sentence summary for administrators.

            Output ONLY a valid JSON object with exactly this structure — no extra text, explanations, or markdown:

            {
            "topic_modeling": [
                {
                "topic_name": "string",
                "keyphrases": ["string"],
                "sentiment": "positive|negative|neutral|mixed",
                "average_rating": number,
                "summary": "string",
                "frequency": number
                }
            ],
            "overall_sentiment": {
                "overall_sentiment": "Positive|Mostly Positive|Mixed|Mostly Negative|Negative",
                "sentiment_score": number,
                "average_rating": number,
                "rating_distribution": {
                "1": number,
                "2": number,
                "3": number,
                "4": number,
                "5": number
                },
                "positive_aspects": ["string"],
                "negative_aspects": ["string"],
                "suggestions_summary": "string"
            },
            "keyphrase_extraction": [
                {
                "phrase": "string",
                "frequency": integer,
                "sentiment": "positive|negative|neutral",
                "sources": "feedback|suggestions|both"
                }
            ],
            "role_insights": "string",
            "language_notes": "string",
            "comprehensive_summary": "string"
            }

            Focus deeply on meaning, even with language mixing. Use ratings to reinforce or adjust text-based sentiment.
        PROMPT;
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

        $content = $data['choices'][0]['message']['content'] ?? null;

        $parsedContent = json_decode(trim($content), true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($parsedContent)) {
            return [
                'content' => $parsedContent, 
                'usage' => $data['usage'] ?? null,
                'model' => $data['model'] ?? null
            ];
        }

        Log::info('Grok API response', [
            'usage' => $data['usage'] ?? null,
            'model' => $data['model'] ?? null
        ]);

        return [
            'content' => $content,
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
