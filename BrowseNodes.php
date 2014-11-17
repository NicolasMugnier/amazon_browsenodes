<?php

require_once(dirname(__FILE__) . '/Ecs.php');

/**
 * Class BrowseNodes : list browse node infos for a specific one or recursively
 *
 * @author Nicolas Mugnier <nicolas@boostmyshop.com>
 * @link http://docs.aws.amazon.com/AWSECommerceService/latest/DG/BrowseNodeIDs.html
 * @link http://docs.aws.amazon.com/AWSECommerceService/latest/DG/CHAP_ApiReference.html
 */
class BrowseNodes {

    /**
     * Récupération des browsenodes enfants pour un id donné
     * 
     * @param int $browseNodeId
     * @param string $country
     * @return string $data
     */
    public function getBrowsenodesById($browseNodeId, $country = 'US') {

        $ecs = new ECS_Browsenodes();
        $cpt = 0;
        do {

            $cpt++;
            $log = ' -> request ' . $browseNodeId . " ($cpt x)\n";
            echo $log;

            $result = $ecs->request($browseNodeId, $country);

        } while ($result['status'] != 200 && $cpt <= 50);
        
        $status = $result['status'];
        $data = $result['data'];

        return $data;
    }

    /**
     * Get All Children
     *
     * @param int $browseNodeId
     * @param string $country
     * @param string $parent
     * @return string
     * @todo : manage filename from results
     */
    public function getAllChildren($browseNodeId, $country, $parent = '') {

        $csv = '';

        $content = $this->getBrowsenodesById($browseNodeId, $country);

        $xml = new DomDocument();
        $xml->loadXML($content);

        if ($xml->getElementsByTagName('BrowseNode')->item(0)) {

            if ($xml->getElementsByTagName('Children')->item(0)) {
                $childrenNode = $xml->getElementsByTagName('Children')->item(0);
                foreach ($childrenNode->getElementsByTagName('BrowseNode') as $node) {
                    $browseNodeId = $node->getElementsByTagName('BrowseNodeId')->item(0)->nodeValue;
                    $name = $node->getElementsByTagName('Name')->item(0)->nodeValue;
                    $path = $parent .' > '.$name;
                    $csv = '"' . $browseNodeId . '","' . $path . '"' . "\n";
                    file_put_contents(dirname(__FILE__).'/SportingGoods_'.$country.'.csv', $csv, FILE_APPEND);
                    echo $csv . "\n";
                    sleep(2);
                    $csv .= $this->getAllChildren($browseNodeId, $country, $path);
                }
            }
        }

        return $csv;
    }

    /**
     * Récupération des browsenodes pour un univers donné et un pays donné
     * 
     * @param string $univers
     * @param string $country
     * @todo : to implement
     */
    public function getBrowsenodesByUnivers($univers, $country) {
        
    }

    /**
     * Récupération des browsenodes pour un pays donné
     * 
     * @param string $country
     * @todo : to implement
     */
    public function getBrowsenodesByCountry($country) {
        
    }

    /**
     * Récupération de tous les browsenodes (optimiste sur ce coup :)
     *
     * @todo : to implement
     * 
     */
    public function getAllBrowsenodes() {
        
    }

}

$shortopt = 'b:'; // browsenode id
$shortopt .= 'c:'; // country
$options = getopt($shortopt);

$tool = new BrowseNodes();
$res = $tool->getAllChildren($options['b'], $options['c'], 'SportingGoods');
