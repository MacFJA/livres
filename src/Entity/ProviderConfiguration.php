<?php
/**
 * @author  MacFJA
 * @license MIT
 */
namespace App\Entity;

use App\Worker\Query\ProviderInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class ProviderConfiguration extends Base
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\Column(type="string")
     * @var string
     */
    protected $class = '';
    /**
     * @ORM\Column(type="json_array")
     * @var array
     */
    protected $arguments = [];
    /**
     * @ORM\Column(type="boolean")
     * @var bool
     */
    protected $active = false;

    /**
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * @return boolean
     */
    public function isActive()
    {
        return $this->active;
    }

    public function __isset(string $name)
    {
        if (in_array($name, ['class', 'active'], true)) {
            return true;
        }

        return array_key_exists($name, $this->arguments);
    }

    public function __get(string $name)
    {
        switch ($name) {
            case 'class':
                return $this->class;
            case 'active':
                return $this->active;
            case 'arguments':
                return $this->arguments;
            default:
                if (!array_key_exists($name, $this->arguments)) {
                    return null;
                }
                return $this->arguments[$name];
        }
    }

    /**
     * @param string $name
     * @param mixed  $value
     * @return mixed
     */
    public function __set(string $name, $value)
    {
        switch ($name) {
            case 'class':
            case 'active':
            case 'arguments':
                return $this->{$name} = $value;
            default:
                return $this->arguments[$name] = $value;
        }
    }

    /**
     * Specify data which should be serialized to JSON
     *
     * @link  http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     *        which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return ['class' => $this->class, 'arguments' => $this->arguments, 'active' => $this->active];
    }
    
    public function getConfiguredProvider(): ProviderInterface
    {
        $reflection = new \ReflectionClass($this->class);
        /** @var ProviderInterface $instance */
        $instance = $reflection->newInstanceArgs($this->arguments);
        return $instance;
    }

    public function getProviderLabel(): string
    {
        $reflection = new \ReflectionClass($this->class);
        $label = $reflection->getMethod('getLabel');

        return $label->invoke(null);
    }
}
