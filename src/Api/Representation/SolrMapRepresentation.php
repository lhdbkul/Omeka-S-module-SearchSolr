<?php declare(strict_types=1);

/*
 * Copyright BibLibre, 2017
 * Copyright Daniel Berthereau, 2020-2025
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

namespace SearchSolr\Api\Representation;

use Omeka\Api\Representation\AbstractEntityRepresentation;

class SolrMapRepresentation extends AbstractEntityRepresentation
{
    /**
     * @var \SearchSolr\Entity\SolrMap
     */
    protected $resource;

    /**
     * @var array
     */
    protected $pool;

    public function getJsonLdType(): string
    {
        return 'o:SolrMap';
    }

    public function getJsonLd(): array
    {
        return [
            'o:solr_core' => $this->solrCore()->getReference(),
            'o:resource_name' => $this->resource->getResourceName(),
            'o:field_name' => $this->resource->getFieldName(),
            'o:alias' => $this->resource->getAlias(),
            'o:source' => $this->resource->getSource(),
            'o:pool' => $this->resource->getPool(),
            'o:settings' => $this->resource->getSettings(),
        ];
    }

    public function adminUrl($action = null, $canonical = false): string
    {
        $url = $this->getViewHelper('Url');
        $params = [
            'action' => $action,
            'id' => $this->id(),
            'core-id' => $this->solrCore()->id(),
            'resource-name' => $this->resourceName(),
        ];
        $options = [
            'force_canonical' => $canonical,
        ];
        return $url('admin/search/solr/core-id-map-resource-id', $params, $options);
    }

    public function solrCore(): ?SolrCoreRepresentation
    {
        $solrCore = $this->resource->getSolrCore();
        return $this->getAdapter('solr_cores')->getRepresentation($solrCore);
    }

    public function resourceName(): string
    {
        return $this->resource->getResourceName();
    }

    public function fieldName(): string
    {
        return $this->resource->getFieldName();
    }

    public function alias(): ?string
    {
        return $this->resource->getAlias();
    }

    public function source(): string
    {
        return $this->resource->getSource();
    }

    public function pool(?string $name = null, $default = null)
    {
        if (!is_null($this->pool)) {
            return is_null($name) ? $this->pool : ($this->pool[$name] ?? $default);
        }

        $this->pool = $this->resource->getPool();

        // Check the regex for value one time.
        if (empty($this->pool['filter_values'])) {
            $this->pool['filter_values'] = null;
        } else {
            $test = @preg_match($this->pool['filter_values'], '');
            if ($test === false) {
                $this->pool['filter_values'] = null;
            }
        }

        // Check the regex for uri one time.
        if (empty($this->pool['filter_uris'])) {
            $this->pool['filter_uris'] = null;
        } else {
            $test = @preg_match($this->pool['filter_uris'], '');
            if ($test === false) {
                $this->pool['filter_uris'] = null;
            }
        }

        // To avoid issues with updating/removing, check the data types.
        $dataTypeManager = $this->getServiceLocator()->get('Omeka\DataTypeManager');
        foreach (['data_types', 'data_types_exclude'] as $dataTypeName) {
            $dataTypes = $this->pool[$dataTypeName] ?? [];
            // Check the data type against the list of registered data types.
            $result = [];
            foreach ($dataTypes as $dataType) {
                if ($dataTypeManager->has($dataType)) {
                    $result[] = $dataType;
                }
            }
            $this->pool[$dataTypeName] = $result;
        }

        if (empty($this->pool['filter_languages'])) {
            $this->pool['filter_languages'] = [];
        } elseif (!is_array($this->pool['filter_languages'])) {
            // Don't filter array to keep values without language.
            $this->pool['filter_languages'] = array_unique(explode(' ', $this->pool['filter_languages']));
        }

        if (empty($this->pool['filter_visibility']) || !in_array($this->pool['filter_visibility'], ['public', 'private'])) {
            $this->pool['filter_visibility'] = null;
        }

        return is_null($name) ? $this->pool : ($this->pool[$name] ?? $default);
    }

    public function settings(): array
    {
        return $this->resource->getSettings();
    }

    public function setting($name, $default = null)
    {
        $settings = $this->resource->getSettings();
        return $settings[$name] ?? $default;
    }

    public function firstSource(): string
    {
        $source = (string) $this->source();
        return strpos($source, '/') === false
            ? $source
            : strtok($source, '/');
    }

    public function subMap(): ?SolrSubMap
    {
        $source = $this->source();
        if (strpos($source, '/') === false) {
            $subField = '';
        } else {
            [, $subField] = explode('/', $source, 2);
        }
        $subMap = new SolrSubMap($this->resource, $this->adapter);
        // TODO Manage a sub pool something like dcterms:creator/skos:altLabel[xxx].
        return $subMap
            ->setSubSource($subField);
    }
}
