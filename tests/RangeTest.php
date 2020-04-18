<?php

use IPTools\Range;
use IPTools\Network;
use IPTools\IP;

class RangeTest extends \PHPUnit\Framework\TestCase
{
	/**
	 * @dataProvider getTestParseData
	 */
	public function testParse($data, $expected)
	{
		$range = Range::parse($data);

		$this->assertEquals($expected[0], $range->firstIP);
		$this->assertEquals($expected[1], $range->lastIP);
	}

	/**
	 * @dataProvider getTestNetworksData
	 */
	public function testGetNetworks($data, $expected)
	{
		$result = array();

		foreach (Range::parse($data)->getNetworks() as $network) {
			$result[] = (string)$network;
		}

		$this->assertEquals($expected, $result);
	}

	/**
	 * @dataProvider getTestContainsData
	 */
	public function testContains($data, $find, $expected)
	{
		$this->assertEquals($expected, Range::parse($data)->contains(new IP($find)));
	}

	/**
	 * @dataProvider getTestContainsAnyData
	 */
	public function testContainsAny($data, $find, $expected)
	{
		$findArray = [];
		foreach ($find as $item) {
			$findArray[] = new IP($item);
		}
		$this->assertEquals($expected, Range::parse($data)->containsAny($findArray));
	}

	/**
	 * @dataProvider getTestContainsAllData
	 */
	public function testContainsAll($data, $find, $expected)
	{
		$findArray = [];
		foreach ($find as $item) {
			$findArray[] = new IP($item);
		}
		$this->assertEquals($expected, Range::parse($data)->containsAll($findArray));
	}

	public function testContainsException()
	{
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Invalid type');
		Range::parse('192.168.1.0/24')->contains(false);
	}

	public function testSetFirstIPException()
	{
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('First IP is grater than second');
		Range::parse('192.168.1.0/25')->setFirstIP(new IP('192.168.1.128'));
	}

	public function testSetLastIPException()
	{
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Last IP is less than first');
		Range::parse('192.168.1.128/25')->setLastIP(new IP('192.168.1.127'));
	}

	/**
	 * @dataProvider getTestIterationData
	 */
	public function testRangeIteration($data, $expected)
	{
		foreach (Range::parse($data) as $key => $ip) {
		   $result[] = (string)$ip;
		}

		$this->assertEquals($expected, $result);
	}

	/**
	 * @dataProvider getTestCountData
	 */
	public function testCount($data, $expected)
	{
		$this->assertEquals($expected, count(Range::parse($data)));
	}

	public function getTestParseData()
	{
		return array(
			array('127.0.0.1-127.255.255.255', array('127.0.0.1', '127.255.255.255')),
			array('127.0.0.1/24', array('127.0.0.0', '127.0.0.255')),
			array('127.*.0.0', array('127.0.0.0', '127.255.0.0')),
			array('127.255.255.0', array('127.255.255.0', '127.255.255.0')),
		);
	}

	public function getTestNetworksData()
	{
		return array(
			array('192.168.1.*', array('192.168.1.0/24')),
			array('192.168.1.208-192.168.1.255', array(
				'192.168.1.208/28',
				'192.168.1.224/27'
			)),
			array('192.168.1.0-192.168.1.191', array(
				'192.168.1.0/25',
				'192.168.1.128/26'
			)),
			array('192.168.1.125-192.168.1.126', array(
				'192.168.1.125/32',
				'192.168.1.126/32',
			)),
		);
	}

	public function getTestContainsData()
	{
		return array(
			array('192.168.*.*', '192.168.245.15', true),
			array('192.168.*.*', '192.169.255.255', false),

			/**
			 * 10.10.45.48 --> 00001010 00001010 00101101 00110000
			 * the last 0000 leads error
			 */
			array('10.10.45.48/28', '10.10.45.58', true),

			array('2001:db8::/64', '2001:db8::ffff', true),
			array('2001:db8::/64', '2001:db8:ffff::', false),
		);
	}

	public function getTestContainsAnyData()
	{
		return array(
			array('192.168.*.*', ['193.168.245.1', '194.168.246.15', '192.168.245.16'], true),
			array('192.168.*.*', ['193.168.245.1', '192.168.245.15', '194.168.245.16'], true),
			array('192.168.*.*', ['196.168.245.1', '195.168.245.15', '194.168.245.16'], false),
			array('192.168.0.0/16', ['193.168.245.1', '194.168.246.15', '192.168.245.16'], true),
			array('192.168.0.0/16', ['193.168.245.1', '192.168.245.15', '194.168.245.16'], true),
			array('192.168.0.0/16', ['196.168.245.1', '195.168.245.15', '194.168.245.16'], false),
		);
	}

	public function getTestContainsAllData()
	{
		return array(
			array('192.168.*.*', ['192.168.245.1', '192.168.246.15', '192.168.245.16'], true),
			array('192.168.*.*', ['193.168.245.1', '192.168.245.15', '194.168.245.16'], false),
			array('192.168.*.*', ['196.168.245.1', '195.168.245.15', '194.168.245.16'], false),
			array('192.168.0.0/16', ['192.168.245.1', '192.168.246.15', '192.168.245.16'], true),
			array('192.168.0.0/16', ['193.168.245.1', '192.168.245.15', '194.168.245.16'], false),
			array('192.168.0.0/16', ['196.168.245.1', '195.168.245.15', '194.168.245.16'], false),
		);
	}

	public function getTestIterationData()
	{
		return array(
			array('192.168.2.0-192.168.2.7',
				array(
					'192.168.2.0',
					'192.168.2.1',
					'192.168.2.2',
					'192.168.2.3',
					'192.168.2.4',
					'192.168.2.5',
					'192.168.2.6',
					'192.168.2.7',
				)
			),
			array('2001:db8::/125',
				array(
					'2001:db8::',
					'2001:db8::1',
					'2001:db8::2',
					'2001:db8::3',
					'2001:db8::4',
					'2001:db8::5',
					'2001:db8::6',
					'2001:db8::7',
				)
			),
		);
	}

	public function getTestCountData()
	{
		return array(
			array('127.0.0.0/31', 2),
			array('2001:db8::/120', 256),
		);
	}
}
