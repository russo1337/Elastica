<?php

namespace Elastica\Test\Aggregation;

use Elastica\Aggregation\Terms;
use Elastica\Document;
use Elastica\Index;
use Elastica\Mapping;
use Elastica\Query;

/**
 * @group functional
 *
 * @internal
 */
class TermsTest extends BaseAggregationTest
{
    /**
     * @group unit
     */
    public function testIncludePattern(): void
    {
        $agg = new Terms('terms');
        $agg->setInclude('pattern*');

        $this->assertSame($agg->getParam('include'), 'pattern*');
    }

    /**
     * @group unit
     */
    public function testIncludeExactMatch(): void
    {
        $agg = new Terms('terms');
        $agg->setIncludeAsExactMatch(['first', 'second']);

        $this->assertSame($agg->getParam('include'), ['first', 'second']);
    }

    /**
     * @group unit
     */
    public function testIncludeWithPartitions(): void
    {
        $agg = new Terms('terms');
        $agg->setIncludeWithPartitions(1, 23);

        $this->assertSame($agg->getParam('include'), [
            'partition' => 1,
            'num_partitions' => 23,
        ]);
    }

    /**
     * @group unit
     */
    public function testExcludePattern(): void
    {
        $agg = new Terms('terms');
        $agg->setExclude('pattern*');

        $this->assertSame($agg->getParam('exclude'), 'pattern*');
    }

    /**
     * @group unit
     */
    public function testExcludeExactMatch(): void
    {
        $agg = new Terms('terms');
        $agg->setExcludeAsExactMatch(['first', 'second']);

        $this->assertSame($agg->getParam('exclude'), ['first', 'second']);
    }

    public function testTermsAggregation(): void
    {
        $agg = new Terms('terms');
        $agg->setField('color');

        $query = new Query();
        $query->addAggregation($agg);
        $results = $this->getIndex()->search($query)->getAggregation('terms');

        $this->assertEquals(2, $results['buckets'][0]['doc_count']);
        $this->assertEquals('blue', $results['buckets'][0]['key']);
    }

    public function testTermsSetOrder(): void
    {
        $agg = new Terms('terms');
        $agg->setField('color');
        $agg->setOrder('_count', 'asc');

        $query = new Query();
        $query->addAggregation($agg);
        $results = $this->getIndex()->search($query)->getAggregation('terms');

        $this->assertEquals('blue', $results['buckets'][2]['key']);
    }

    public function testTermsSetOrders(): void
    {
        $agg = new Terms('terms');
        $agg->setField('color');
        $agg->setOrders([
            ['_count' => 'asc'], // 1. red,   2. green, 3. blue
            ['_key' => 'asc'],   // 1. green, 2. red,   3. blue
        ]);

        $query = new Query();
        $query->addAggregation($agg);
        $results = $this->getIndex()->search($query)->getAggregation('terms');

        $this->assertSame('green', $results['buckets'][0]['key']);
        $this->assertSame('red', $results['buckets'][1]['key']);
        $this->assertSame('blue', $results['buckets'][2]['key']);
    }

    private function getIndex(): Index
    {
        $index = $this->_createIndex();

        $mapping = new Mapping([
            'color' => ['type' => 'keyword'],
        ]);
        $index->setMapping($mapping);

        $index->addDocuments([
            new Document(1, ['color' => 'blue']),
            new Document(2, ['color' => 'blue']),
            new Document(3, ['color' => 'red']),
            new Document(4, ['color' => 'green']),
        ]);

        $index->refresh();

        return $index;
    }
}