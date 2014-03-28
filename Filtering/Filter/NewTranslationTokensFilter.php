<?php

namespace Modera\BackendTranslationsToolBundle\Filtering\Filter;

/**
 * @author    Sergei Vizel <sergei.vizel@modera.org>
 * @copyright 2014 Modera Foundation
 */
class NewTranslationTokensFilter extends AbstractTranslationTokensFilter
{
    /**
     * {@inheritDoc}
     */
    public function getId()
    {
        return 'new';
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'New';
    }

    /**
     * {@inheritDoc}
     */
    public function getCount(array $params)
    {
        if (!isset($params['filter'])) {
            $params['filter'] = $this->getFilter();
        }
        return parent::getCount($params);
    }

    /**
     * {@inheritDoc}
     */
    public function getResult(array $params)
    {
        $params['filter'] = $this->getFilter();
        return parent::getResult($params);
    }

    /**
     * @return array
     */
    private function getFilter()
    {
        try {
            $q = $this->em()->createQuery(
                'SELECT IDENTITY(ltt.translationToken) as translationToken ' .
                'FROM ModeraTranslationsBundle:LanguageTranslationToken ltt ' .
                'WHERE ltt.isNew=true GROUP BY ltt.translationToken'
            );
            $result = $q->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);
        } catch (\Exception $e) {
            $result = array();
        }

        $ids = array_map(function($row) {
            return $row['translationToken'];
        }, $result);

        $filter[] = ['property' => 'isObsolete', 'value' => 'eq:false'];
        $filter[] = ['property' => 'id', 'value' => 'in:' . implode(',', $ids)];

        return $filter;
    }
} 