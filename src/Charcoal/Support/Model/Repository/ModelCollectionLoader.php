<?php

namespace Charcoal\Support\Model\Repository;

use RuntimeException;

// From 'charcoal-core'
use Charcoal\Model\ModelInterface;

/**
 * Model Collection Loader
 *
 * Provides support for cloning, preventing model swapping,
 * and sharing the same {@see \Charcoal\Source\SourceInterface source}.
 */
class ModelCollectionLoader extends CollectionLoaderIterator
{
    /**
     * Track whether the collection loader is a clone.
     *
     * @var boolean
     */
    protected $isClone = false;

    /**
     * Track whether the collection loader model is locked.
     *
     * @var boolean
     */
    protected $lockModel = false;

    /**
     * Clone the collection loader.
     *
     * @return void
     */
    public function __clone()
    {
        $this->isClone   = true;
        $this->lockModel = false;
    }

    /**
     * Clone the collection loader.
     *
     * @param  mixed $data An array of customizations for the clone or an object model.
     * @return static
     */
    public function cloneWith($data)
    {
        if (!is_array($data)) {
            $data = [
                'model' => $data,
            ];
        }

        $defaults = [
            'logger'     => $this->logger,
            'collection' => $this->collectionClass(),
            'factory'    => $this->factory(),
            'model'      => $this->model(),
        ];

        $data = array_merge($defaults, $data);

        $clone = new static($data);

        $typeField = $this->dynamicTypeField();
        if ($typeField) {
            $clone->setDynamicTypeField($typeField);
        }

        $callback = $this->callback();
        if ($callback) {
            $clone->setCallback($callback);
        }

        return $clone;
    }

    /**
     * Set the model to use for the loaded objects.
     *
     * This method is overriden to prevent model swapping. To reuse the same loader
     * with a different model, you must {@see self::cloneWith() clone the loader}.
     *
     * @param  string|ModelInterface $model An object model.
     * @throws RuntimeException If this method is called a second time.
     * @return self
     */
    public function setModel($model)
    {
        if ($this->hasModel() && $this->lockModel) {
            throw new RuntimeException(
                sprintf(
                    'A model is already assigned to this collection loader: %s',
                    get_class($this->model())
                )
            );
        }

        $this->lockModel = true;
        parent::setModel($model);
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return \Charcoal\Source\SourceInterface
     */
    public function source()
    {
        $this->lockModel = true;
        return parent::source();
    }

    /**
     * Create a new model.
     *
     * @return ModelInterface
     */
    public function createModel()
    {
        $model = $this->factory()->create($this->modelClass());
        $model->setSource($this->source());
        return $model;
    }

    /**
     * Create a new model from a dataset.
     *
     * @param  array $data The model data.
     * @return ModelInterface
     */
    protected function createModelFromData(array $data)
    {
        $model = $this->factory()->create($this->dynamicModelClass($data));
        $model->setSource($this->source());
        return $model;
    }
}
