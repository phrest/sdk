<?php

namespace Phrest\SDK\Generator\Helper;

use PhpParser\Lexer;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\Stmt\UseUse;
use PhpParser\Parser;
use Phrest\SDK\Generator;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\PropertyGenerator;
use Zend\Code\Reflection\ClassReflection;

class ClassDiff
{
  /**
   * @var string
   */
  protected $currentFilePath;

  /**
   * @var string
   */
  protected $currentClassName;

  /**
   * @var string
   */
  protected $currentClassCode;

  /**
   * @var ClassGenerator
   */
  protected $currentClassGenerator;

  /**
   * @var ClassGenerator
   */
  protected $newClassGenerator;

  /**
   * ClassDiff constructor.
   *
   * @param string         $currentFilePath
   * @param string         $currentClassName
   * @param ClassGenerator $newClassGenerator
   */
  public function __construct(
    $currentFilePath,
    $currentClassName,
    ClassGenerator $newClassGenerator
  )
  {
    $this->currentFilePath = $currentFilePath;
    $this->currentClassName = $currentClassName;
    $this->currentClassCode = file_get_contents($currentFilePath);
    $this->currentClassGenerator = $this->getGeneratorFromReflection(
      new ClassReflection($currentClassName)
    );
    $this->newClassGenerator = $newClassGenerator;

    /*
     * PHP Reflections don't take into account use statements, so an entire
     * plugin is needed just for that. //shakes head
     */
    $parser = new Parser(new Lexer());

    $nodes = $parser->parse($this->currentClassCode);

    foreach ($nodes as $node)
    {
      /** @var $node Namespace_ */
      if (get_class($node) == 'PhpParser\Node\Stmt\Namespace_')
      {
        /** @var Use_ $stmt */
        foreach ($node->stmts as $stmt)
        {
          if (get_class($stmt) == 'PhpParser\Node\Stmt\Use_')
          {
            /** @var UseUse $use */
            foreach ($stmt->uses as $use)
            {
              $this->currentClassGenerator->addUse($use->name->toString());
            }
          }
        }
      }
    }

    if (in_array(
      ltrim($this->currentClassGenerator->getExtendedClass(), '\\'),
      $this->currentClassGenerator->getUses()
    ))
    {
      $extended = new \ReflectionClass(
        $this->currentClassGenerator->getExtendedClass()
      );
      $this->currentClassGenerator->setExtendedClass($extended->getShortName());
    }
  }

  /**
   * @return ClassGenerator
   */
  public function merge()
  {
    $current = $this->currentClassGenerator;
    $new = $this->newClassGenerator;

    foreach ($new->getMethods() as $newMethod)
    {
      $currentMethod = $current->getMethod($newMethod->getName());
      if ($currentMethod)
      {
        if (Generator::$force)
        {
          $current->removeMethod($currentMethod->getName());
          $current->addMethodFromGenerator($newMethod);
        }
      }
      else
      {
        $current->addMethodFromGenerator($newMethod);
      }
    }

    return $current;
  }

  /**
   * Copied from ClassGenerator::fromReflection and tweaked slightly
   *
   * @param ClassReflection $classReflection
   *
   * @return ClassGenerator
   */
  public function getGeneratorFromReflection(ClassReflection $classReflection)
  {
    // class generator
    $cg = new ClassGenerator($classReflection->getName());

    $cg->setSourceContent($cg->getSourceContent());
    $cg->setSourceDirty(false);

    if ($classReflection->getDocComment() != '')
    {
      $docblock
        = DocBlockGenerator::fromReflection($classReflection->getDocBlock());
      $docblock->setIndentation(Generator::$indentation);
      $cg->setDocBlock($docblock);
    }

    $cg->setAbstract($classReflection->isAbstract());

    // set the namespace
    if ($classReflection->inNamespace())
    {
      $cg->setNamespaceName($classReflection->getNamespaceName());
    }

    /* @var \Zend\Code\Reflection\ClassReflection $parentClass */
    $parentClass = $classReflection->getParentClass();
    if ($parentClass)
    {
      $cg->setExtendedClass('\\' . ltrim($parentClass->getName(), '\\'));
      $interfaces = array_diff($classReflection->getInterfaces(),
                               $parentClass->getInterfaces());
    }
    else
    {
      $interfaces = $classReflection->getInterfaces();
    }

    $interfaceNames = array();
    foreach ($interfaces as $interface)
    {
      /* @var \Zend\Code\Reflection\ClassReflection $interface */
      $interfaceNames[] = $interface->getName();
    }

    $cg->setImplementedInterfaces($interfaceNames);

    $properties = array();
    foreach ($classReflection->getProperties() as $reflectionProperty)
    {
      if (
        $reflectionProperty->getDeclaringClass()->getName()
        == $classReflection->getName()
      )
      {
        $property = PropertyGenerator::fromReflection($reflectionProperty);
        $property->setIndentation(Generator::$indentation);
        $properties[] = $property;
      }
    }
    $cg->addProperties($properties);

    $methods = array();
    foreach ($classReflection->getMethods() as $reflectionMethod)
    {
      $className
        = ($cg->getNamespaceName()) ? $cg->getNamespaceName() . "\\" . $cg->getName() : $cg->getName();
      if ($reflectionMethod->getDeclaringClass()->getName() == $className)
      {
        $method = MethodGenerator::fromReflection($reflectionMethod);
        $method->setBody(
          preg_replace(
            "/^" . Generator::$indentation . Generator::$indentation . "/m",
            '',
            $method->getBody()
          )
        );
        $method->setIndentation(Generator::$indentation);
        $methods[] = $method;
      }
    }
    $cg->addMethods($methods);

    return $cg;
  }
}
