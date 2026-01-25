<?php

namespace dee\inertia;

use yii\web\Request;
use yii\db\QueryInterface;
use yii\data\ActiveDataProvider;
use yii\data\DataProviderInterface;
use yii\base\InvalidArgumentException;

/**
 * Description of ScrollProp 
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class ScrollProp extends BaseProp implements Mergeable
{
    use MergesProps;
    /**
     * @var string
     */
    protected $wrapper = 'data';
    /**
     * @var array
     */
    protected $options;

    /**
     * @var DataProviderInterface
     */
    private $_dataProvider;

    /**
     * @param DataProviderInterface|QueryInterface $value
     * @param string $wrapper
     * @param array $options
     */
    public function __construct($value, $wrapper = 'data', $options = [])
    {
        if (!($value instanceof DataProviderInterface || $value instanceof QueryInterface)) {
            throw new InvalidArgumentException("Value must instance of 'yii\data\DataProviderInterface' or 'yii\db\QueryInterface'");
        }
        $this->value = $value;
        $this->wrapper = $wrapper;
        $this->options = $options ?: [];
    }

    /**
     * Configure the merge strategy based on the infinite scroll merge intent header.
     *
     * The frontend InfiniteScroll component sends its merge intent directly,
     * eliminating the need for direction-based logic on the backend.
     * @param Request $request
     * @return static
     */
    public function configureMergeIntent(Request $request): static
    {
        return $request->headers->get(Header::INFINITE_SCROLL_MERGE_INTENT) === 'prepend'
            ? $this->prepend($this->wrapper)
            : $this->append($this->wrapper);
    }

    /**
     * @return DataProviderInterface
     */
    public function getDataProvider()
    {
        if ($this->_dataProvider === null) {
            if ($this->value instanceof DataProviderInterface) {
                $this->_dataProvider = $this->value;
            } elseif ($this->value instanceof QueryInterface) {
                $configs = [
                    'query' => $this->value,
                ];
                if (isset($this->options['pagination'])) {
                    $configs['pagination'] = $this->options['pagination'];
                }
                if (isset($this->options['sort'])) {
                    $configs['sort'] = $this->options['sort'];
                }
                $this->_dataProvider = new ActiveDataProvider($configs);
            }
        }
        return $this->_dataProvider;
    }

    /**
     * @return array
     */
    public function __invoke()
    {
        return [
            $this->wrapper => $this->getDataProvider()->getModels(),
        ];
    }

    /**
     * @return array|null
     */
    public function getMeta()
    {
        if ($pagination = $this->getDataProvider()->getPagination()) {
            $current = $pagination->getPage() + 1;
            $count = $pagination->getPageCount();
            return [
                'pageName' => $pagination->pageParam,
                'previousPage' => $current > 1 ? $current - 1 : null,
                'nextPage' => $current < $count ? $current + 1 : null,
                'currentPage' => $current,
                'pageCount' => $count,
            ];
        }
    }
}
