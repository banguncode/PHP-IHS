<?php

namespace BangunCode\Ihs\Tests;

use BangunCode\Ihs\Fhir\Bundle;
use BangunCode\Ihs\Fhir\IdentifierSystem;
use BangunCode\Ihs\Fhir\JsonPatch;
use BangunCode\Ihs\Fhir\Payload;
use BangunCode\Ihs\Fhir\ResourceType;
use BangunCode\Ihs\Fhir\TransactionBundle;
use BangunCode\Ihs\Fhir\Uuid;

final class FhirTest extends TestCase
{
    public function testTransactionBundleEntries()
    {
        $bundle = new TransactionBundle();
        $encounter = $bundle->create(array('resourceType' => 'Encounter'));
        $condition = $bundle->create(
            array('resourceType' => 'Condition', 'encounter' => array('reference' => $encounter)),
            'c566d6e2-4da0-4895-9bcb-8051dd16548c'
        );
        $bundle->update(array('resourceType' => 'Location', 'id' => 'loc-1'));

        $data = $bundle->toArray();
        $this->assertSame('transaction', $data['type']);
        $this->assertCount(3, $bundle);
        $this->assertSame(1, preg_match('/^urn:uuid:[0-9a-f-]{36}$/', $encounter));
        $this->assertSame('urn:uuid:c566d6e2-4da0-4895-9bcb-8051dd16548c', $condition);
        $this->assertSame(array('method' => 'POST', 'url' => 'Condition'), $data['entry'][1]['request']);
        $this->assertSame($encounter, $data['entry'][1]['resource']['encounter']['reference']);
        $this->assertSame(array('method' => 'PUT', 'url' => 'Location/loc-1'), $data['entry'][2]['request']);

        $this->captureException('BangunCode\Ihs\Exception\InvalidArgumentException', function () use ($bundle) {
            $bundle->create(array('status' => 'final'));
        });
    }

    public function testJsonPatch()
    {
        $patch = JsonPatch::create()->add('/a', 1)->replace('/b', 2)->remove('/c')->move('/d', '/e');

        $this->assertSame('[{"op":"add","path":"/a","value":1},{"op":"replace","path":"/b","value":2},'
            . '{"op":"remove","path":"/c"},{"op":"move","from":"/d","path":"/e"}]', Payload::encode($patch->toArray()));
        $this->assertSame($patch, JsonPatch::from($patch));
        $this->assertCount(1, JsonPatch::from('[{"op":"remove","path":"/x"}]'));

        $this->captureException('BangunCode\Ihs\Exception\InvalidArgumentException', function () {
            JsonPatch::create()->add('status', 'x');
        });
        $this->captureException('BangunCode\Ihs\Exception\InvalidArgumentException', function () {
            JsonPatch::from(array(array('value' => 1)));
        });
    }

    public function testSearchBundle()
    {
        $bundle = new Bundle(array(
            'resourceType' => 'Bundle',
            'type' => 'searchset',
            'total' => 3,
            'link' => array(array('relation' => 'self', 'url' => 'https://x/DocumentReference')),
            'entry' => array(
                array('resource' => array('resourceType' => 'DocumentReference', 'id' => 'd')),
                array('resource' => array('resourceType' => 'MedicationRequest', 'id' => 'm1')),
                array('resource' => array('resourceType' => 'MedicationRequest', 'id' => 'm2')),
            ),
        ));

        $this->assertSame(3, $bundle->getTotal());
        $this->assertCount(3, $bundle);
        $this->assertCount(2, $bundle->getResources('MedicationRequest'));
        $this->assertSame('d', $bundle->first()['id']);
        $this->assertNull($bundle->first('Observation'));
        $this->assertFalse($bundle->hasNextPage());
        $this->assertSame('https://x/DocumentReference', $bundle->getLink('self'));
        $this->assertCount(3, iterator_to_array($bundle));
    }

    public function testTransactionResponseIds()
    {
        $bundle = new Bundle(array('entry' => array(
            array('response' => array('status' => '201 Created', 'resourceType' => 'Condition', 'resourceID' => 'c1')),
        )));

        $this->assertSame(
            array(array('resourceType' => 'Condition', 'id' => 'c1', 'status' => '201 Created')),
            $bundle->getCreatedIds()
        );
    }

    public function testIdentifierSystems()
    {
        $this->assertSame('http://sys-ids.kemkes.go.id/servicerequest/100', IdentifierSystem::forOrganization('servicerequest', '100'));
        $this->assertSame('http://sys-ids.kemkes.go.id/prescription-item/100', IdentifierSystem::forOrganization('prescription-item', 100));
        $this->assertSame('https://sys-ids.kemkes.go.id/device/100', IdentifierSystem::device('100'));
        $this->assertSame('http://sys-ids.kemkes.go.id/diagnostic/100/lab', IdentifierSystem::diagnosticReport('100', 'lab'));
        $this->assertSame('https://sys-ids.kemkes.go.id/insurance-subscriber/200', IdentifierSystem::insuranceSubscriber('200'));
        $this->assertSame('https://sys-ids.kemkes.go.id/insurance-number/200', IdentifierSystem::insuranceNumber('200'));

        $this->captureException('BangunCode\Ihs\Exception\InvalidArgumentException', function () {
            IdentifierSystem::forOrganization('encounter', '');
        });
        $this->captureException('BangunCode\Ihs\Exception\InvalidArgumentException', function () {
            IdentifierSystem::diagnosticReport('100', 'xray');
        });
    }

    public function testUuid()
    {
        $uuid = Uuid::v4();

        $this->assertTrue(Uuid::isValid($uuid));
        $this->assertSame('4', $uuid[14]);
        $this->assertTrue(in_array($uuid[19], array('8', '9', 'a', 'b'), true));
        $this->assertNotSame($uuid, Uuid::v4());
        $this->assertSame(0, strpos(Uuid::urn(), 'urn:uuid:'));
    }

    public function testPayloadAndResourceTypes()
    {
        $this->assertSame(array('a' => 1), Payload::toArray('{"a":1}'));
        $this->assertSame('{"url":"http://x/y","text":"Gedung Utama"}', Payload::encode(array('url' => 'http://x/y', 'text' => 'Gedung Utama')));
        $this->captureException('BangunCode\Ihs\Exception\InvalidArgumentException', function () {
            Payload::toArray('{invalid');
        });

        $this->assertCount(46, ResourceType::all());
        $this->assertTrue(ResourceType::isKnown('QuestionnaireResponse'));
        $this->assertFalse(ResourceType::isKnown('Basic'));
        $this->assertTrue(ResourceType::isValidName('Basic'));
    }
}
