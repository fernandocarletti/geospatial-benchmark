<?php
$solr =  new SolrClient(array('hostname' => 'localhost', 'port' => 8983, 'path' => 'solr/collection1'));
$solr->deleteByQuery('*:*');
$solr->commit();

$lines = file('polygnos.txt');
$total = count($lines);

for ($i = 0; $i <= $total; $i++) {
    $document = new SolrInputDocument();
    $document->addField('id', ($i + 1));
    $document->addField('bounds', trim($lines[$i]));

    $solr->addDocument($document);
    $solr->commit();
    echo '.';
}
