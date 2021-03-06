<?php

/*
 * This file is part of PHP DNS Server.
 *
 * (c) Yif Swery <yiftachswr@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace yswery\DNS\Tests\Resolver;

use PHPUnit\Framework\TestCase;
use yswery\DNS\ClassEnum;
use yswery\DNS\RecordTypeEnum;
use yswery\DNS\Resolver\JsonResolver;
use yswery\DNS\Resolver\ResolverInterface;
use yswery\DNS\ResourceRecord;

abstract class AbstractResolverTest extends TestCase
{
    /**
     * @var ResolverInterface
     */
    protected $resolver;

    public function testGetAnswer()
    {
        $soa = (new ResourceRecord())
            ->setName('example.com.')
            ->setClass(ClassEnum::INTERNET)
            ->setTtl(10800)
            ->setType(RecordTypeEnum::TYPE_SOA)
            ->setRdata([
                'mname' => 'example.com.',
                'rname' => 'postmaster.example.com.',
                'serial' => 2,
                'refresh' => 3600,
                'retry' => 7200,
                'expire' => 10800,
                'minimum' => 3600,
            ]);

        $aaaa = (new ResourceRecord())
            ->setName('example.com.')
            ->setClass(ClassEnum::INTERNET)
            ->setTtl(7200)
            ->setType(RecordTypeEnum::TYPE_AAAA)
            ->setRdata('2001:acad:ad::32');

        $soa_query = (new ResourceRecord())
            ->setName('example.com.')
            ->setType(RecordTypeEnum::TYPE_SOA)
            ->setClass(ClassEnum::INTERNET)
            ->setQuestion(true);

        $aaaa_query = (new ResourceRecord())
            ->setName('example.com.')
            ->setType(RecordTypeEnum::TYPE_AAAA)
            ->setClass(ClassEnum::INTERNET)
            ->setQuestion(true);

        $query = [$soa_query, $aaaa_query];
        $answer = [$soa, $aaaa];

        $this->assertEquals($answer, $this->resolver->getAnswer($query));
    }

    public function testUnconfiguredRecordDoesNotResolve()
    {
        $question[] = (new ResourceRecord())
            ->setName('testestestes.com.')
            ->setType(RecordTypeEnum::TYPE_A)
            ->setQuestion(true);

        $this->assertEmpty($this->resolver->getAnswer($question));
    }

    public function testHostRecordReturnsArray()
    {
        $question[] = (new ResourceRecord())
            ->setName('test2.com.')
            ->setType(RecordTypeEnum::TYPE_A)
            ->setQuestion(true);

        $expectation[] = (new ResourceRecord())
            ->setName('test2.com.')
            ->setType(RecordTypeEnum::TYPE_A)
            ->setTtl(300)
            ->setRdata('111.111.111.111');

        $expectation[] = (new ResourceRecord())
            ->setName('test2.com.')
            ->setType(RecordTypeEnum::TYPE_A)
            ->setTtl(300)
            ->setRdata('112.112.112.112');

        $this->assertEquals($expectation, $this->resolver->getAnswer($question));
    }

    public function testWildcardDomains()
    {
        $question[] = (new ResourceRecord())
            ->setName('badcow.subdomain.example.com.')
            ->setType(RecordTypeEnum::TYPE_A)
            ->setQuestion(true);

        $expectation[] = (new ResourceRecord())
            ->setName('badcow.subdomain.example.com.')
            ->setType(RecordTypeEnum::TYPE_A)
            ->setTtl(7200)
            ->setRdata('192.168.1.42');

        $this->assertEquals($expectation, $this->resolver->getAnswer($question));
    }

    /**
     * @throws \yswery\DNS\UnsupportedTypeException
     */
    public function testIsWildcardDomain()
    {
        $resolver = new JsonResolver([]);
        $this->assertTrue($resolver->isWildcardDomain('*.cat.com.'));
        $this->assertFalse($resolver->isWildcardDomain('github.com.'));
    }

    public function testAllowsRecursion()
    {
        $this->assertFalse($this->resolver->allowsRecursion());
    }

    public function testIsAuthority()
    {
        $this->assertTrue($this->resolver->isAuthority('example.com.'));
    }

    public function testSrvRdata()
    {
        $question[] = (new ResourceRecord())
            ->setName('_ldap._tcp.example.com.')
            ->setType(RecordTypeEnum::TYPE_SRV)
            ->setQuestion(true);

        $expectation[] = (new ResourceRecord())
            ->setName('_ldap._tcp.example.com.')
            ->setType(RecordTypeEnum::TYPE_SRV)
            ->setTtl(7200)
            ->setRdata([
                'priority' => 1,
                'weight' => 5,
                'port' => 389,
                'target' => 'ldap.example.com.',
            ]);

        $this->assertEquals($expectation, $this->resolver->getAnswer($question));
    }
}
