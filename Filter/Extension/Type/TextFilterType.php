<?php

namespace Lexik\Bundle\FormFilterBundle\Filter\Extension\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\Options;

use Lexik\Bundle\FormFilterBundle\Filter\Expr;

use Doctrine\ORM\QueryBuilder;

/**
 * Filter type for strings.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class TextFilterType extends AbstractFilterType implements FilterTypeInterface
{
    const PATTERN_EQUALS     = Expr::STRING_EQ;
    const PATTERN_START_WITH = Expr::STRING_STARTS;
    const PATTERN_END_WITH   = Expr::STRING_ENDS;
    const PATTERN_CONTAINS   = Expr::STRING_BOTH;

    const SELECT_PATTERN = 'select_pattern';

    /**
     * @var string
     */
    protected $transformerId;

    protected $parent;

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $attributes          = array();
        $this->transformerId = 'lexik_form_filter.transformer.default';

        if ($options['condition_pattern'] == self::SELECT_PATTERN) {
            $textOptions             = array_intersect_key($options, parent::getDefaultOptions(array()));
            $textOptions['required'] = isset($options['required']) ? $options['required'] : false;
            $textOptions['trim']     = isset($options['trim']) ? $options['trim'] : true;

            $builder->add('condition_pattern', 'choice', array(
                'choices' => self::getConditionChoices(),
            ));
            $builder->add('text', 'text', $textOptions);

            $this->transformerId = 'lexik_form_filter.transformer.text';
        } else {
            parent::buildForm($builder, $options);

            $attributes['condition_pattern'] = $options['condition_pattern'];
        }

        $builder->setAttribute('filter_options', $attributes);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);

        $compound = function (Options $options) {
            return $options['condition_pattern'] == TextFilterType::SELECT_PATTERN;
        };

        $resolver->setDefaults(array(
            'condition_pattern' => self::PATTERN_EQUALS,
            'compound' => $compound,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'text';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'filter_text';
    }

    /**
     * {@inheritdoc}
     */
    public function getTransformerId()
    {
        return $this->transformerId;
    }

    /**
     * {@inheritdoc}
     */
    public function applyFilter(QueryBuilder $queryBuilder, Expr $expr, $field, array $values)
    {
        if (!empty($values['value'])) {
            $queryBuilder->andWhere($expr->stringLike($field, $values['value'], $values['condition_pattern']));
        }
    }

    /**
     * Retruns an array of available conditions patterns.
     *
     * @return array
     */
    static private function getConditionChoices()
    {
        $choices = array();

        $reflection = new \ReflectionClass(__CLASS__);
        foreach ($reflection->getConstants() as $name => $value) {
            if ('PATTERN_' === substr($name, 0, 8)) {
                $choices[$value] = strtolower(str_replace(array('PATTERN_', '_'), array('', ' '), $name));
            }
        }

        return $choices;
    }
}
