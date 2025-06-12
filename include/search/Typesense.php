<?php

use GuzzleHttp\Client;

require_once __DIR__ . '/TypesenseDocumentType.php';

class Typesense extends Teleskope
{
    private static ?Client $client = null;

    private const TYPESENSE_COLLECTION = 'affinity';
    private const RESULTS_PER_PAGE = 10;

    private static function GetClient(): Client
    {
        if (!is_null(self::$client)) {
            return self::$client;
        }

        self::$client = new Client([
            'base_uri' => 'http://' . Config::Get('TYPESENSE_HOST') . ':8108',
            'headers' => [
                'X-TYPESENSE-API-KEY' => Config::Get('TYPESENSE_ADMIN_API_KEY'),
            ]
        ]);

        return self::$client;
    }

    public static function CreateCollection(): void
    {
        $schema = [
            'name' => self::TYPESENSE_COLLECTION,
            'fields' => [
                [
                    'name' => 'company_id',
                    'type' => 'int32',
                ],
                [
                    'name' => 'zone_id',
                    'type' => 'int32',
                ],
                [
                    'name' => 'type',
                    'type' => 'string',
                ],
                [
                    'name' => 'title',
                    'type' => 'string',
                ],
                [
                    'name' => 'description',
                    'type' => 'string',
                ],
                [
                    'name' => 'group_id',
                    'type' => 'int32',
                ],
                [
                    'name' => 'chapter_ids',
                    'type' => 'int32[]',
                    'optional' => true,
                ],
                [
                    'name' => 'channel_ids',
                    'type' => 'int32[]',
                    'optional' => true,
                ],
                [
                    'name' => 'published_at',
                    'type' => 'int64',
                    'optional' => true,
                ],
                [
                    'name' => 'created_at',
                    'type' => 'int64',
                    'optional' => true,
                ],
                [
                    'name' => 'status',
                    'type' => 'string',
                    'optional' => true,
                ],
                [
                    'name' => 'hashtag_ids',
                    'type' => 'int32[]',
                    'optional' => true,
                ],
            ],
        ];

        self::GetClient()->delete('/collections/' . self::TYPESENSE_COLLECTION, [
            'http_errors' => false,
        ]);

        self::GetClient()->post('/collections', [
            'json' => $schema,
        ]);
    }

    public static function UploadCollection(array $collection): void
    {
        if (!count($collection)) {
            return;
        }

        $json_lines = array_reduce($collection, function (string $carry, Teleskope $model) {
            if ($model->searchable()) {
                return $carry . json_encode($model->getTypesenseDocument()) . "\n";
            }
            return $carry;
        }, '');

        self::GetClient()->post('/collections/' . self::TYPESENSE_COLLECTION . '/documents/import', [
            'query' => [
                'action' => 'create',
            ],
            'body' => $json_lines,
            'headers' => [
                'Content-Type' => 'text/plain',
            ],
        ]);
    }

    public static function Search(
        string $query,
        array $filters = [],
        int $page = 1,
        int $per_page = self::RESULTS_PER_PAGE
    ): array
    {
        global $_COMPANY, $_ZONE;

        $filters['company_id'] = $_COMPANY->id();
        $filters['zone_id'] = $_ZONE->id();
        $filter_by = [];
        foreach ($filters as $key => $value) {
            if ($value) {
                if (is_array($value)) {
                    $value = json_encode($value);
                }

                $filter_by[] = "{$key}: {$value}";
            }
        }
        $filter_by_str = implode(' && ', $filter_by);

        $response = self::GetClient()->get(
            '/collections/' . self::TYPESENSE_COLLECTION . '/documents/search',
            [
                'query' => [
                    'q' => $query,
                    'query_by' => 'title,description',
                    'filter_by' => $filter_by_str,
                    'enable_highlight_v1' => false,
                    'page' => $page,
                    'per_page' => $per_page,
                ],
            ]
        );

        $search_results = json_decode($response->getBody(), true);
        return self::ProcessSearchResults($search_results);
    }

    private static function ProcessSearchResults(array $search_results): array
    {
        return [
            'found' => $search_results['found'],
            'page' => $search_results['page'],
            'hits' => array_map(function (array $result) {
                return [
                    'document' => $result['document'],
                    'highlight' => $result['highlight'],
                ];
            }, $search_results['hits']),
        ];
    }

    public static function UploadModel(Teleskope $model): void
    {
        if (!Config::Get('ENABLE_GLOBAL_SEARCH')) {
            return;
        }

        self::GetClient()->post('/collections/' . self::TYPESENSE_COLLECTION . '/documents', [
            'query' => [
                'action' => 'upsert',
            ],
            'json' => $model->getTypesenseDocument(),
        ]);
    }

    public static function DeleteDocument(string $document_id): void
    {
        if (!Config::Get('ENABLE_GLOBAL_SEARCH')) {
            return;
        }

        self::GetClient()->delete(
            (string) self::$client->getConfig('base_uri') . '/collections/' . self::TYPESENSE_COLLECTION . '/documents/' . $document_id,
            [
                'http_errors' => false,
            ]
        );
    }

    public static function GetDocumentId(string $model_class, int $id): string
    {
        return self::GetDocumentType($model_class) . ':' . $id;
    }

    public static function GetDocumentType(string $model_class): string
    {
        return constant("TypesenseDocumentType::{$model_class}")->value;
    }

    public static function GetModelById(string $document_id): ?Teleskope
    {
        [$model_name, $model_id] =  explode(':', $document_id);
        return static::GetModel($model_name, $model_id);
    }

    public static function GetModel(string $model_name, int $model_id): ?Teleskope
    {
        $model_class = self::GetModelClass($model_name);
        $getter_fn_name = 'Get' . $model_class;
        return call_user_func([$model_class, $getter_fn_name], $model_id);
    }

    public static function GetModelClass(string $model_name): string
    {
        return Str::ConvertSnakeCaseToCamelCase($model_name);
    }

    public static function GetModelId(string $document_id): int
    {
        [$model_name, $model_id] = explode(':', $document_id);
        return $model_id;
    }

}
