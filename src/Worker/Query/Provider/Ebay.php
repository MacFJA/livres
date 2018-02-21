<?php
/**
  * @author  MacFJA
  * @license MIT
  */
namespace App\Worker\Query\Provider;

use App\Worker\Query\QueryResult;
use DTS\eBaySDK\Finding\Services\FindingService;
use DTS\eBaySDK\Finding\Types\BaseFindingServiceRequest;
use DTS\eBaySDK\Finding\Types\BaseFindingServiceResponse;
use DTS\eBaySDK\Finding\Types\FindItemsByKeywordsRequest;
use DTS\eBaySDK\Finding\Types\FindItemsByProductRequest;
use DTS\eBaySDK\Finding\Types\ProductId;

class Ebay extends BaseIsbn
{
    /** @var string */
    protected $devId;
    /** @var string */
    protected $appId;
    /** @var string */
    protected $certId;

    /**
     * Ebay constructor.
     *
     * @param string $devId  Developer Identifier
     * @param string $appId  Application Identifier
     * @param string $certId
     */
    public function __construct($devId, $appId, $certId)
    {
        $this->devId = $devId;
        $this->appId = $appId;
        $this->certId = $certId;
    }

    private function getResponse(string $field, string $value): BaseFindingServiceResponse
    {
        $service = new FindingService(['credentials' => [
            'devId' => $this->devId,
            'appId' => $this->appId,
            'certId' => $this->certId
        ]]);

        $request = new FindItemsByKeywordsRequest();
        $request->keywords = $value;

        if (in_array($field, ['ean', 'upc', 'isbn', static::FIELD_INTERNAL, 'epid'], true)) {
            $request = new FindItemsByProductRequest();
            $productId = new ProductId();
            $productId->value = $value;
            $productId->type = in_array($field, [static::FIELD_INTERNAL, 'epid'], true)
                ? 'ReferenceID'
                : strtoupper($field);
            $request->productId = $productId;
        }

        return  ($request instanceof  FindItemsByProductRequest)
            ? $service->findItemsByProduct($request)
            : $service->findItemsByKeywords($request);
    }

    /**
     * @param string $field
     * @param string $value
     * @return QueryResult[]
     */
    public function search(string $field, string $value): array
    {
        $response = $this->getResponse($field, $value);

        if (empty($response->errorMessage)) {
            return [];
        }
        if ($response->ack == 'Failure') {
            return [];
        }
        if (!isset($response->searchResult->item)) {
            return [];
        }

        $result = [];

        foreach ($response->searchResult->item as $searchItem) {
            $result[] = QueryResult::createSimple($this, $field, $value, $searchItem, [
                'title' => $searchItem->title,
                'cover' => $searchItem->pictureURLLarge,
                'subtitle' => $searchItem->subtitle,
                'ebay_link' => $searchItem->viewItemURL
            ]);
        }
        return $result;
    }

    public function getCode(): string
    {
        return 'ebay';
    }

    public static function getLabel(): string
    {
        return 'Ebay';
    }
}
