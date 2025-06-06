<?php declare(strict_types=1);

/*
 * Copyright BibLibre, 2016
 * Copyright Daniel Berthereau, 2017-2025
 *
 * This software is governed by the CeCILL license under French law and abiding
 * by the rules of distribution of free software.  You can use, modify and/ or
 * redistribute the software under the terms of the CeCILL license as circulated
 * by CEA, CNRS and INRIA at the following URL "http://www.cecill.info".
 *
 * As a counterpart to the access to the source code and rights to copy, modify
 * and redistribute granted by the license, users are provided only with a
 * limited warranty and the software's author, the holder of the economic
 * rights, and the successive licensors have only limited liability.
 *
 * In this respect, the user's attention is drawn to the risks associated with
 * loading, using, modifying and/or developing or reproducing the software by
 * the user in light of its specific status of free software, that may mean that
 * it is complicated to manipulate, and that also therefore means that it is
 * reserved for developers and experienced professionals having in-depth
 * computer knowledge. Users are therefore encouraged to load and test the
 * software's suitability as regards their requirements in conditions enabling
 * the security of their systems and/or data to be ensured and, more generally,
 * to use and operate it in the same conditions as regards security.
 *
 * The fact that you are presently reading this means that you have had
 * knowledge of the CeCILL license and that you accept its terms.
 */

namespace SearchSolr\Api\Adapter;

use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;

class SolrCoreAdapter extends AbstractEntityAdapter
{
    use TraitArrayFilterRecursiveEmptyValue;

    protected $sortFields = [
        'id' => 'id',
        'name' => 'name',
    ];

    protected $scalarFields = [
        'id' => 'id',
        'name' => 'name',
        'settings' => 'settings',
    ];

    public function getResourceName()
    {
        return 'solr_cores';
    }

    public function getRepresentationClass()
    {
        return \SearchSolr\Api\Representation\SolrCoreRepresentation::class;
    }

    public function getEntityClass()
    {
        return \SearchSolr\Entity\SolrCore::class;
    }

    public function buildQuery(QueryBuilder $qb, array $query): void
    {
        $expr = $qb->expr();

        // Id is managed via entity adapter.

        if (isset($query['name']) && $query['name']) {
            $qb->andWhere($expr->eq(
                'omeka_root.name',
                $this->createNamedParameter($qb, $query['name'])
            ));
        }
    }

    public function hydrate(Request $request, EntityInterface $entity, ErrorStore $errorStore): void
    {
        /** @var \SearchSolr\Entity\SolrCore $entity */
        if ($this->shouldHydrate($request, 'o:name')) {
            $entity->setName(trim($request->getValue('o:name') ?? ''));
        }
        if ($this->shouldHydrate($request, 'o:settings')) {
            $array = $this->arrayFilterRecursiveEmptyValue($request->getValue('o:settings') ?: []);
            $entity->setSettings($array);
        }
    }

    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore): void
    {
        if (!trim($entity->getName() ?? '')) {
            $errorStore->addError('o:name', 'The name cannot be empty.'); // @translate
        }
    }
}
