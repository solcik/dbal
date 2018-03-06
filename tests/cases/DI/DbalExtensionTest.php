<?php declare(strict_types = 1);

namespace Tests\Nettrine\DBAL\DI;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\ContainerLoader;
use Nettrine\DBAL\DI\DbalExtension;
use Tests\Nettrine\DBAL\TestCase;

final class DbalExtensionTest extends TestCase
{

	/**
	 * @return void
	 */
	public function testRegister(): void
	{
		$loader = new ContainerLoader(TEMP_PATH, TRUE);
		$class = $loader->load(function (Compiler $compiler): void {
			$compiler->addExtension('dbal', new DbalExtension());
			$compiler->addConfig(['dbal' => ['connection' => ['driver' => 'pdo_sqlite']]]);
		}, '1a');

		/** @var Container $container */
		$container = new $class();
		/** @var Connection $connection */
		$connection = $container->getByType(Connection::class);
		$qb = $connection->createQueryBuilder();

		self::assertInstanceOf(Connection::class, $connection);
		self::assertInstanceOf(QueryBuilder::class, $qb);

		$connection->executeQuery(
			'CREATE TABLE person (id int, lastname varchar(255), firstname varchar(255), address varchar(255), city varchar(255));'
		);

		$qb->insert('person')
			->values([
				'id' => 1,
				'firstname' => '"John"',
				'lastname' => '"Doe"',
			])
			->execute();

		$qb->insert('person')
			->values([
				'id' => 2,
				'firstname' => '"Sam"',
				'lastname' => '"Smith"',
			])
			->execute();

		$qb = $connection->createQueryBuilder();
		$select = $qb->select('id', 'firstname')
			->from('person')
			->execute();

		self::assertEquals([
			[
				'id' => '1',
				'firstname' => 'John',
			],
			[
				'id' => '2',
				'firstname' => 'Sam',
			],
		], $select->fetchAll());
	}

}
