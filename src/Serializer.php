<?php

namespace dee\inertia;

use yii\rest\Serializer as RestSerializer;

/**
 * Description of Event
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class Serializer extends RestSerializer
{
    /**
     * {@inheritDoc}
     */
    public $collectionEnvelope = 'items';
    /**
     * {@inheritDoc}
     */
    public $linksEnvelope = 'links';
    /**
     * {@inheritDoc}
     */
    public $metaEnvelope = 'meta';
    /**
     * @var int
     */
    public $maxPageButton = 5;

    /**
     * {@inheritDoc}
     */
    protected function serializeModelErrors($model)
    {
        foreach ($model->getFirstErrors() as $key => $error) {
            Inertia::$errors[$key] = $error;
        }    
        return parent::serializeModel($model);
    }

    /**
     * {@inheritDoc}
     */
    protected function serializePagination($pagination)
    {
        $currentPage = $pagination->getPage();
        $pageCount = $pagination->getPageCount();
        $result = [
            $this->metaEnvelope => [
                'totalCount' => $pagination->totalCount,
                'pageCount' => $pageCount,
                'currentPage' => $currentPage + 1,
                'perPage' => $pagination->getPageSize(),
            ],
        ];
        if ($this->linksEnvelope) {
            $maxPageButton = $this->maxPageButton;
            $links = [];
            if ($pageCount > 0) {
                $links[] = ['label' => 'first', 'href' => $pagination->createUrl(0, null, true), 'active' => $currentPage == 0];
                if ($currentPage > 0) {
                    $links[] = ['label' => 'prev', 'href' => $pagination->createUrl($currentPage - 1, null, true)];
                }
                $beginPage = max(0, $currentPage - (int) ($maxPageButton / 2));
                if (($endPage = $beginPage + $maxPageButton - 1) >= $pageCount) {
                    $endPage = $pageCount - 1;
                    $beginPage = max(0, $endPage - $maxPageButton + 1);
                }
                for ($i = $beginPage; $i <= $endPage; $i++) {
                    $links[] = ['label' => $i + 1, 'href' => $pagination->createUrl($i, null, true), 'active' => $currentPage == $i];
                }
                if ($currentPage < $pageCount - 1) {
                    $links[] = ['label' => 'next', 'href' => $pagination->createUrl($currentPage + 1, null, true)];
                }
                $links[] = ['label' => 'last', 'href' => $pagination->createUrl($pageCount - 1, null, true), 'active' => $currentPage == $pageCount - 1];
            }
            $result[$this->linksEnvelope] = $links;
        }
        return $result;
    }
}
