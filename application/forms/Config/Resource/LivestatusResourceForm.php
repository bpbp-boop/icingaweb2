<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Forms\Config\Resource;

use Exception;
use Icinga\Web\Form;
use Icinga\Application\Icinga;
use Icinga\Data\ConfigObject;
use Icinga\Data\ResourceFactory;

/**
 * Form class for adding/modifying livestatus resources
 */
class LivestatusResourceForm extends Form
{
    /**
     * Initialize this form
     */
    public function init()
    {
        $this->setName('form_config_resource_livestatus');
    }

    /**
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
        $this->addElement(
            'text',
            'name',
            array(
                'required'      => true,
                'label'         => t('Resource Name'),
                'description'   => t('The unique name of this resource')
            )
        );
        $this->addElement(
            'text',
            'socket',
            array(
                'required'      => true,
                'label'         => t('Socket'),
                'description'   => t('The path to your livestatus socket used for querying monitoring data'),
                'value'         => realpath(Icinga::app()->getApplicationDir() . '/../var/rw/livestatus')
            )
        );

        return $this;
    }

    /**
     * Validate that the current configuration points to a valid resource
     *
     * @see Form::onSuccess()
     */
    public function onSuccess()
    {
        if (false === static::isValidResource($this)) {
            return false;
        }
    }

    /**
     * Validate the resource configuration by trying to connect with it
     *
     * @param   Form    $form   The form to fetch the configuration values from
     *
     * @return  bool            Whether validation succeeded or not
     */
    public static function isValidResource(Form $form)
    {
        try {
            $resource = ResourceFactory::createResource(new ConfigObject($form->getValues()));
            $resource->connect()->disconnect();
        } catch (Exception $e) {
            $form->addError(t('Connectivity validation failed, connection to the given resource not possible.'));
            return false;
        }

        return true;
    }
}
