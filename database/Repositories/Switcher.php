<?php

namespace Repositories;

use Doctrine\ORM\EntityRepository;
use Entities\CoreBundle;
use Entities\Switcher as SwitcherEntity;

/**
 * Switcher
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class Switcher extends EntityRepository
{
    /**
     * The cache key for all switch objects
     * @var string The cache key for all switch objects
     */
    const ALL_CACHE_KEY = 'inex_switches';

    /**
     * Return an array of all switch objects from the database with caching
     *
     * @param bool $active If `true`, return only active switches
     * @param int $type If `0`, all types otherwise limit to specific type
     * @return array An array of all switch objects
     */
    public function getAndCache( $active = false, $type = 0 )
    {
        $dql = "SELECT s FROM Entities\\Switcher s WHERE 1=1";

        $key = $this->genCacheKey( $active, $type );

        if( $active )
            $dql .= " AND s.active = 1";

        if( $type )
            $dql .= " AND s.switchtype = " . intval( $type );

        return $this->getEntityManager()->createQuery( $dql )
            ->useResultCache( true, 3600, $key )
            ->getResult();
    }


    /**
     * Clear the cache of a given result set
     *
     * @param bool $active If `true`, return only active switches
     * @param int $type If `0`, all types otherwise limit to specific type
     *
     * @return bool
     */
    public function clearCache( $active = false, $type = 0 )
    {
        return $this->getEntityManager()->getConfiguration()->getQueryCacheImpl()->delete(
            $this->genCacheKey( $active, $type )
        );
    }

    /**
     * Clear the cache of all result sets
     */
    public function clearCacheAll()
    {
        foreach( [ true, false ] as $active ) {
            foreach( \Entities\Switcher::$TYPES as $type => $name ) {
                $this->getEntityManager()->getConfiguration()->getQueryCacheImpl()->delete(
                    $this->genCacheKey( $active, $type )
                );
            }
        }
    }

    /**
     * Generate a deterministic caching key for given parameters
     *
     * @param bool $active If `true`, return only active switches
     * @param int $type If `0`, all types otherwise limit to specific type
     * @return string The generate caching key
     */
    public function genCacheKey( $active, $type )
    {
        $key = self::ALL_CACHE_KEY;

        if( $active )
            $key .= '-active';
        else
            $key .= '-all';

        if( $type )
            $key .= '-' . intval( $type );
        else
            $key .= '-all';

        return $key;
    }

    /**
     * Return an array of all switch names where the array key is the switch id
     *
     * @param bool          $active If `true`, return only active switches
     * @param int           $type   If `0`, all types otherwise limit to specific type
     * @param \Entities\IXP $ixp    IXP to filter vlan names
     * @return array An array of all switch names with the switch id as the key.
     */
    public function getNames( $active = false, $type = 0, $ixp = false )
    {
        $switches = [];
        foreach( $this->getAndCache( $active, $type ) as $a )
        {
            if( !$ixp || ( $ixp->getInfrastructures()->contains( $a->getInfrastructure() ) ) )
                $switches[ $a->getId() ] = $a->getName();
        }

        asort( $switches );
        return $switches;
    }

    /**
     * Return an array of all switch names where the array key is the switch id
     *
     * @param \Entities\Infrastructure      $infra
     * @param \Entities\Location            $location

     * @return array An array of all switch names with the switch id as the key.
     */
    public function getByLocationAndInfrastructure( $infra = null, $location = null )
    {
        $q = "SELECT s

            FROM \\Entities\\Switcher s";

        if( $location !== null ){
            $q .= " LEFT JOIN s.Cabinet cab";
        }


        $q .= " WHERE 1=1 ";

        if( $infra )
            $q .= 'AND s.Infrastructure = ' .  $infra->getId() . ' ';

        if( $location )
            $q .= 'AND cab.Location = ' . $location->getId() . ' ';


        $q .= " ORDER BY s.name ASC";

        $query = $this->getEntityManager()->createQuery( $q );

        return $query->getResult();
    }

    /**
     * Return an array of all switch names where the array key is the switch id
     *
     * @param bool          $active If `true`, return only active switches
     * @param int           $type   If `0`, all types otherwise limit to specific type
     * @param int           $idLocation  location requiered
     * @return array An array of all switch names with the switch id as the key.
     */
    public function getNamesByLocation( $active = false, $type = 0, $idLocation = null )
    {
        $switches = [];
        foreach( $this->getAndCache( $active, $type ) as $a ) {

            if($idLocation != null)
                if($a->getCabinet()->getLocation()->getId() == $idLocation)
                    $switches[ $a->getId() ] = $a->getName();
        }

        asort( $switches );
        return $switches;
    }


    /**
     * Return an array of configurations
     *
     * @param int   $switchid     Switcher id for filtering results
     * @param int   $vlanid       Vlan id for filtering results
     * @param int   $ixpid        IXP id for filtering results
     * @param bool  $superuser    Does the user is super user ?
     * @param int   $infra        Infrastructure id for filtering results
     * @param int   $facility     Facility id for filtering results
     *
     * @return array
     */
    public function getConfiguration( $switchid = null, $vlanid = null, $ixpid = null, $superuser = true, $infra = null, $facility = null )
    {
        $q =
            "SELECT s.name AS switchname, 
                    s.id AS switchid,
                    
                    GROUP_CONCAT(  sp.ifName ) AS ifName,
                    GROUP_CONCAT( pi.speed ) AS speed, 
                    pi.duplex AS duplex, 
                    pi.status AS portstatus,
                    c.name AS customer, 
                    c.id AS custid, 
                    c.autsys AS asn,
                    vli.rsclient AS rsclient,
                    v.name AS vlan,
                    GROUP_CONCAT( DISTINCT ipv4.address ) AS ipv4address, 
                    GROUP_CONCAT( DISTINCT ipv6.address ) AS ipv6address

            FROM \\Entities\\VlanInterface vli
                JOIN vli.IPv4Address ipv4
                LEFT JOIN vli.IPv6Address ipv6
                LEFT JOIN vli.Vlan v
                LEFT JOIN vli.VirtualInterface vi
                LEFT JOIN vi.Customer c
                LEFT JOIN vi.PhysicalInterfaces pi
                LEFT JOIN pi.SwitchPort sp
                LEFT JOIN sp.Switcher s
                LEFT JOIN v.Infrastructure vinf
                LEFT JOIN vinf.IXP vixp
                LEFT JOIN s.Infrastructure sinf
                LEFT JOIN sinf.IXP sixp
                LEFT JOIN s.Cabinet cab

            WHERE 1=1 ";

        if( $switchid !== null )
            $q .= 'AND s.id = ' . intval( $switchid ) . ' ';

        if( $vlanid !== null )
            $q .= 'AND v.id = ' . intval( $vlanid ) . ' ';

        if( $ixpid !== null )
            $q .= 'AND ( sixp.id = ' . intval( $ixpid ) . ' OR vixp.id = ' . intval( $ixpid ) . ' ) ';

        if( !$superuser && $ixpid )
            $q .= 'AND ?1 MEMBER OF c.IXPs ';

        if( $infra !== null )
            $q .= 'AND sinf.id = ' . intval( $infra ) . ' ';

        if( $facility !== null )
            $q .= 'AND cab.Location = ' . intval( $facility ) . ' ';


        $q .= " GROUP BY switchname, switchid, duplex, portstatus, customer, custid, asn, rsclient, vlan ";

        $q .= " ORDER BY customer ASC";

        $query = $this->getEntityManager()->createQuery( $q );

        if( !$superuser && $ixpid )
            $query->setParameter( 1, $ixpid );

        return $query->getArrayResult();
    }


    /**
     * Get all active switches as Doctrine2 objects
     *
     * @return array
     */
    public function getActive()
    {
        $q = "SELECT s FROM \\Entities\\Switcher s WHERE s.active = 1";
        return $this->getEntityManager()->createQuery( $q )->getResult();
    }

    /**
     * Returns all switch ports for a given switch.
     *
     * Each switchport element of the array is as follows:
     *
     *      [
     *          "sp_type" => 5,
     *          "sp_name" => "Management Port",
     *          "sp_active" => true,
     *          "sp_ifIndex" => 1059,
     *          "sp_ifName" => "Management",
     *          "sp_ifAlias" => "MgmtPort",
     *          "sp_ifHighSpeed" => 1000,
     *          "sp_ifMtu" => 1500,
     *          "sp_ifPhysAddress" => "0004968F9A4F",
     *          "sp_ifAdminStatus" => 1,
     *          "sp_ifOperStatus" => 1,
     *          "sp_ifLastChange" => 1473091000,
     *          "sp_lastSnmpPoll" => DateTime {#1382
     *          +"date": "2016-10-26 15:11:31.000000",
     *              +"timezone_type": 3,
     *              +"timezone": "UTC",
     *          },
     *          "sp_lagIfIndex" => null,
     *          "sp_mauType" => "1000BaseTFD",
     *          "sp_mauState" => "operational",
     *          "sp_mauAvailability" => "available",
     *          "sp_mauJacktype" => "rj45S",
     *          "sp_mauAutoNegSupported" => true,
     *          "sp_mauAutoNegAdminState" => true,
     *          "sp_id" => 1525,
     *          "pi_id" => null,
     *          "sp_switchid" => 35,
     *          "sp_type_name" => "Management",
     *          ],
     *
     * @param int      $id     Switch ID - switch to query
     * @return array
     */
    public function getPorts( int $id ): array {

        $dql = "SELECT sp, pi.id AS pi_id
                    FROM Entities\\SwitchPort sp
                      LEFT JOIN sp.Switcher s
                      LEFT JOIN sp.PhysicalInterface pi
                    WHERE s.id = ?1
                    ORDER BY sp.id ASC";

        $ports = $this->getEntityManager()->createQuery( $dql )
                    ->setParameter( 1, $id )
                    ->setHint(\Doctrine\ORM\Query::HINT_INCLUDE_META_COLUMNS, true)
                    ->getScalarResult();

        foreach( $ports as $id => $port )
            $ports[$id]['sp_type_name'] = \Entities\SwitchPort::$TYPES[ $port['sp_type'] ];

        return $ports;
    }

    /**
     * Returns all available switch ports where available means not in use by a
     * patch panel port.
     *
     * Function specifically for use with the patch panel ports functionality.
     *
     * Not suitable for other generic use.
     *
     * @param int      $id     Switch ID - switch to query
     * @param int|null $cid    Customer ID, if set limit to a customer's ports
     * @param int|null $spid   Switch port ID, if set, this port is excluded from the results
     * @return array
     */
    public function getAllPortsForPPP( int $id, int $cid = null, int $spid = null ): array {

        /** @noinspection SqlNoDataSourceInspection */
        $dql = "SELECT sp.name AS name, sp.type AS type, sp.id AS id
                    FROM \\Entities\\SwitchPort sp
                        LEFT JOIN sp.Switcher s
                        LEFT JOIN sp.PhysicalInterface pi ";


        if( $cid != null ) {
            $dql .= " LEFT JOIN pi.VirtualInterface vi 
                      LEFT JOIN vi.Customer c";
        }

        // Remove the switch ports already in use by all patch panels
        $dql .= " WHERE sp.id NOT IN ( SELECT IDENTITY(ppp.switchPort)
                    FROM Entities\\PatchPanelPort ppp
                    WHERE ppp.switchPort IS NOT NULL";

        if( $spid != null ) {
            $dql .= " AND ppp.switchPort != $spid";
        }

        $dql .= ") AND s.id = ?1";

        if( $cid != null ) {
            $dql .= " AND c.id = $cid";
        }

        $dql .= " ORDER BY sp.id ASC";

        $query = $this->getEntityManager()->createQuery( $dql );
        $query->setParameter( 1, $id);

        $ports = $query->getArrayResult();

        foreach( $ports as $id => $port ){
            $ports[$id]['type'] = \Entities\SwitchPort::$TYPES[ $port['type'] ];
        }

        return $ports;
    }


    /**
     * Returns all available switch ports where available means not in use by a
     * patch panel port and not assigned to a physical interface.
     *
     * Function specifically for use with the patch panel ports functionality.
     *
     * Not suitable for other generic use.
     *
     * @param int      $id     Switch ID - switch to query
     * @param int|null $spid   Switch port ID, if set, this port is excluded from the results
     * @return array
     */
    public function getAllPortsPrewired( int $id, int $spid = null ): array {
        /** @noinspection SqlNoDataSourceInspection */
        $dql = "SELECT sp.name AS name, sp.type AS type, sp.id AS id
                    FROM \\Entities\SwitchPort sp
                        LEFT JOIN sp.Switcher s ";


        // Remove the switch port already use by a patch panel port
        $dql .= " WHERE sp.id NOT IN (SELECT IDENTITY(ppp.switchPort)
                                      FROM Entities\\PatchPanelPort ppp
                                      WHERE ppp.switchPort IS NOT NULL";

        if( $spid !== null ){
            $dql .= " AND ppp.switchPort != $spid";
        }

        $dql .= ") AND s.id = ?1";


        $dql .= " AND sp.id NOT IN (SELECT IDENTITY(pi.SwitchPort)
                                      FROM Entities\\PhysicalInterface pi)";

        $dql .= " AND ((sp.type = 0)  OR (sp.type = 1))";

        $dql .= " ORDER BY sp.id ASC";

        $query = $this->getEntityManager()->createQuery( $dql );
        $query->setParameter( 1, $id);

        $ports = $query->getArrayResult();

        foreach( $ports as $id => $port ){
            $ports[$id]['type'] = \Entities\SwitchPort::$TYPES[ $port['type'] ];
        }

        return $ports;
    }

    /**
     * Returns all available switch ports for a switch which are not assigned to a physical interface.
     *
     *
     * @param int      $id     Switch ID - switch to query
     * @param array    $types  Array of switch port types to limit the results to, if empty - return all types
     * @param int|null $spid   Switch port ID, if set, this port is excluded from the results
     * @return array
     */
    public function getAllPortsNotAssignedToPI( int $id, array $types = [], int $spid = null ): array {

        $dql = "SELECT sp.name AS name, sp.type AS typeid, sp.id AS id
                    FROM Entities\\SwitchPort sp
                        LEFT JOIN sp.Switcher s
                        LEFT JOIN sp.PhysicalInterface pi
                    WHERE
                        s.id = ?1 
                        AND pi.id IS NULL ";

        if( $spid !== null ) {
            $dql .= 'AND sp.id != ?2 ';
        }

        // limit to ports suitable for peering?
        if( $types !== [] ) {
            $dql .= 'AND sp.type IN ( ?3 )';
        }

        $dql .= " ORDER BY sp.id ASC";

        $query = $this->getEntityManager()->createQuery( $dql );
        $query->setParameter( 1, $id );

        if( $spid  !== null ) {
            $query->setParameter( 2, $spid );
        }

        if( $types !== [] ) {
            $query->setParameter( 3, $types );
        }

        $ports = $query->getArrayResult();

        // resolve port types into names:
        foreach( $ports as $id => $port ) {
            $ports[ $id ][ 'type' ] = \Entities\SwitchPort::$TYPES[ $port[ 'typeid' ] ];
        }

        return $ports;
    }

    /**
     * Returns all available switch ports for a switch.
     *
     * Restrict to only some types of switch port
     * Exclude switch port ids from the list
     *
     * Suitable for other generic use.
     *
     * @param int      $id     Switch ID - switch to query
     * @param array    $types  Switch port type restrict to some types only
     * @param array    $spid   Switch port IDs, if set, those ports are excluded from the results
     * @return array
     */
    public function getAllPorts( int $id, $types = [], $spid = [], bool $notAssignToPI = true ): array {

        $dql = "SELECT  sp.name AS name, 
                        sp.type AS type, 
                        sp.id AS id, 
                        sp.type AS porttype
                FROM Entities\\SwitchPort sp
                LEFT JOIN sp.Switcher s";

        if( $notAssignToPI ){
            $dql .= " LEFT JOIN sp.PhysicalInterface pi";
        }

        $dql .= " WHERE s.id = ?1 ";

        if( $notAssignToPI ){
            $dql .= " AND pi.id IS NULL ";
        }

        if( count( $spid ) > 0 ){
            $dql .= ' AND sp.id NOT IN ('.implode( ',', $spid ).') ';
        }

        if( count( $types ) > 0 ){
            $dql .= ' AND sp.type IN ('.implode( ',', $types ).') ';
        }

        $dql .= " ORDER BY sp.id ASC ";

        $query = $this->getEntityManager()->createQuery( $dql );
        $query->setParameter( 1, $id );

        $ports = $query->getArrayResult();

        foreach( $ports as $id => $port )
            $ports[$id]['type'] = \Entities\SwitchPort::$TYPES[ $port['type'] ];

        return $ports;
    }

    /**
     * Returns all switch ports assigned to a physical interface for a switch.
     *
     * @param int      $id     Switch ID - switch to query
     *
     * @return array
     */
    public function getAllPortsAssignedToPI( int $id ): array {

        $dql = "SELECT  sp.id AS id, 
                        sp.name AS name, 
                        sp.type AS porttype,
                        pi.speed AS speed, 
                        pi.duplex AS duplex, 
                        c.name AS custname

                FROM Entities\\SwitchPort sp
                    JOIN sp.PhysicalInterface pi
                    JOIN pi.VirtualInterface vi
                    JOIN vi.Customer c

                WHERE sp.Switcher = ?1

                ORDER BY id ASC";


        $query = $this->getEntityManager()->createQuery( $dql );
        $query->setParameter( 1, $id );

        $ports = $query->getArrayResult();

        return $ports;
    }

    /**
     * Returns all the vlan associated to the following switch ID
     *
     * @param int      $id     Switch ID - switch to query
     * @return array
     */
    public function getAllVlan( int $id ): array {

        /** @noinspection SqlNoDataSourceInspection */
        $dql = "SELECT vl.name, vl.private, vl.number, vl.config_name
                    FROM Entities\\VlanInterface vli
                    LEFT JOIN vli.Vlan vl
                    LEFT JOIN vli.VirtualInterface vi
                    LEFT JOIN vi.PhysicalInterfaces pi
                    LEFT JOIN pi.SwitchPort sp
                    LEFT JOIN sp.Switcher s
                    WHERE s.id = ?1 
                    GROUP BY vl.id";

        $query = $this->getEntityManager()->createQuery( $dql );
        $query->setParameter( 1, $id);

        $listVlan = $query->getArrayResult();

        $vlans = [];

        foreach( $listVlan as $vlan ){
            $vlans[] = $vlan;
        }

        return $vlans;
    }

    /**
     * Returns all the core link interface associated to the following switch ID
     *
     * @param int      $id     Switch ID - switch to query
     * @return array
     */
    public function getAllCoreLinkInterfaces( int $id ): array {

        $cis = [];

        foreach( [ 'A', 'B' ] as $side ) {
            /** @noinspection SqlNoDataSourceInspection */
            $dql = "SELECT cb.type, cb.ipv4_subnet as cbSubnet,cb.enabled as cbEnabled, cl.enabled as clEnabled, cb.description, cl.bfd, sp$side.name, pi$side.speed, cl.ipv4_subnet as clSubnet, s$side.id as saId
                        FROM Entities\\CoreLink cl
                        LEFT JOIN cl.coreBundle cb

                        LEFT JOIN cl.coreInterfaceSide$side ci$side

                        LEFT JOIN ci$side.physicalInterface pi$side

                        LEFT JOIN pi$side.SwitchPort sp$side

                        LEFT JOIN sp$side.Switcher s$side

                        WHERE cb.type IN ( ".CoreBundle::TYPE_ECMP.",".CoreBundle::TYPE_L3_LAG." )   

                        AND s$side.id = ?1";

            $query = $this->getEntityManager()->createQuery( $dql );
            $query->setParameter( 1, $id);

            $listCoreInterface = $query->getArrayResult();

            # XXX this need to be refactored as it no longer exports CoreLinkInterface information
            foreach( $listCoreInterface as $ci ){
                $export = [];
                $subnet = ( $ci[ 'type' ] == CoreBundle::TYPE_ECMP ) ? $ci['clSubnet'] : $ci['cbSubnet'];

                $export[ 'ipv4' ]         = $this->linkAddr( $subnet, $side, true );
                $export[ 'description' ]  = $ci[ 'description' ];
                $export[ 'bfd' ]          = $ci[ 'bfd' ];
                $export[ 'speed' ]        = $ci[ 'speed' ];
                $export[ 'name' ]         = $ci[ 'name' ];
                $export[ 'shutdown' ]     = $ci[ 'cbEnabled' ] && $ci[ 'clEnabled' ] ? false : true;

                $cis[] = $export;
            }
        }

        return $cis;
    }

    public function linkAddr( $net, $side, $maskneeded = true ){
        $ip   = explode("/", $net)[0];
        $mask = explode("/", $net)[1];

        $net = ip2long($ip) & (0xffffffff << (32 - $mask));
        $firstip = ($mask == 31) ? $net : $net + 1;

        if( $side == 'A') {
            $ip = long2ip ($firstip);
        } else {
            $ip = long2ip ($firstip + 1);
        }

        if( $maskneeded ){
            $ip .= "/" . $mask;
        }

        return $ip;
    }


     /**
     * Returns the loopback interface information associated with the specified switch ID
     *
     * @param int      $id     Switch ID - switch to query
     * @return array
     */
    public function getLoopbackInfo( int $id ): array {

        $cis = [];

        $sw = $this->getEntityManager( )->getRepository('Entities\Switcher')->find( $id );

        if ($sw && $sw->getLoopbackIP() && $sw->getLoopbackName()) {
            $ci['description']  = 'Loopback interface';
            $ci['loopback']     = true;
            $ci['ipv4']         = $sw->getLoopbackIP() ? $sw->getLoopbackIP().'/32' : null;
            $ci['name']         = $sw->getLoopbackName();
            $ci['shutdown']     = false;

            $cis[] = $ci;
        }

        return $cis;
    }

    /**
     * List of all the switch loopback IP addresses on the same infrastructure as this switch, but excluding the switch's own loopback IP address
     *
     * @param bool     $excludeCurrentSwitch    Exclude the switch for the final result
     * @param int      $id     Switch ID - switch to query
     * @return array
     */
    public function getFloodList( int $id, bool $excludeCurrentSwitch = true ){
        $dql = "SELECT  s.loopback_ip 
                    FROM Entities\\Switcher s
                        WHERE s.Infrastructure = (SELECT inf.id
                                                    FROM Entities\\Switcher s2
                                                    LEFT JOIN s2.Infrastructure inf
                                                    WHERE s2.id = ?1)
                        AND s.loopback_ip IS NOT NULL";

        if( $excludeCurrentSwitch ){
            $dql .= " AND s.id != ".$id;
        }

        $query = $this->getEntityManager()->createQuery( $dql );
        $query->setParameter( 1, $id);

        $listFlood = $query->getArrayResult();

        return array_column( $listFlood, "loopback_ip");
    }

    /**
     * Returns all the bgp associated to the following switch ID
     *
     * @param int      $id     Switch ID - switch to query
     * @return array
     */
    public function getAllNeighbors( int $id ): array {
        $dql = "SELECT  cb.type, cb.ipv4_subnet as cbSubnet, cb.cost, cb.preference, cl.ipv4_subnet as clSubnet, sA.id as sAid, sB.id as sBid, sA.name as sAname , sB.name as sBname, sA.asn as sAasn , sB.asn as sBasn
                    FROM Entities\\CoreLink cl
                    LEFT JOIN cl.coreBundle cb
                    LEFT JOIN cl.coreInterfaceSideA ciA
                    LEFT JOIN cl.coreInterfaceSideB ciB
                    LEFT JOIN ciA.physicalInterface piA
                    LEFT JOIN ciB.physicalInterface piB
                    LEFT JOIN piA.SwitchPort spA
                    LEFT JOIN piB.SwitchPort spB
                    LEFT JOIN spA.Switcher sA
                    LEFT JOIN spB.Switcher sB
                    WHERE ( sA.id = ?1 OR sB.id = ?1 )
                    AND cb.type IN ( ".CoreBundle::TYPE_ECMP.",".CoreBundle::TYPE_L3_LAG." )";


            $query = $this->getEntityManager()->createQuery( $dql );
            $query->setParameter( 1, $id);

            $listbgp = $query->getArrayResult();

            $neighbors = [];
            foreach( $listbgp as $bgp ){
                $side = ( $bgp[ 'sAid' ] == $id ) ? 'B' : 'A';
                $subnet = ( $bgp[ 'type' ] == CoreBundle::TYPE_ECMP ) ? $bgp['clSubnet'] : $bgp['cbSubnet'];
                $neighbors[] = [
                    'ip' => $this->linkAddr( $subnet , $side , false ),
                    'description' => $bgp[ 's' .$side. 'name'],
                    'asn' => $bgp[ 's' .$side. 'asn'],
                    'cost' => $bgp[ 'cost'],
                    'preference' => $bgp[ 'preference'],
                ] ;
            }

        return $neighbors;
    }

    /**
     * Returns all the Vlan on the insfrascture to the following switch ID
     *
     * @param int      $id     Switch ID - switch to query
     * @return array
     */
    public function getAllVlansInInfrastructure( int $id ): array {

        /** @noinspection SqlNoDataSourceInspection */
        $dql = "SELECT vl.name, vl.number as tag, vl.private, vl.config_name
                    FROM Entities\\Infrastructure inf
                        LEFT JOIN inf.Switchers s
                        LEFT JOIN inf.Vlans vl
                        WHERE s.id = ?1";

        $query = $this->getEntityManager()->createQuery( $dql );
        $query->setParameter( 1, $id);

        $listVlan = $query->getArrayResult();

        return $listVlan;
    }

    /**
     * Returns all the Console Server Connection for the given switch ID
     *
     * @deprecated     This will be removed before v5 is released. Just used
     *                 for console server migration.
     * @param int      $id     Switch ID - switch to query
     * @return array
     */
    public function getConsoleServerConnections( int $id ): array {
        /** @noinspection SqlNoDataSourceInspection */
        return $this->getEntityManager()->createQuery(
            "SELECT csc FROM Entities\ConsoleServerConnection csc
                  WHERE csc.switchid = {$id}"
            )->getResult();
    }

    /**
     * Get all switches (or a particular one) for listing on the frontend CRUD
     *
     * @see \IXP\Http\Controller\Doctrine2Frontend
     *
     *
     * @param \stdClass $feParams
     * @param int|null $id
     * @return array Array of switches (as associated arrays) (or single element if `$id` passed)
     */
    public function getAllForFeList( \stdClass $feParams, int $id = null, $params = null )
    {



//        if( $this->getParam( 'infra', false ) && $infra = $this->getD2R( '\\Entities\\Infrastructure' )->find( $this->getParam( 'infra' ) ) )
//        {
//            $qb->andWhere( 'i = :infra' )->setParameter( 'infra', $infra );
//            $this->view->infra = $infra;
//        }

        $dql = "SELECT  s.id AS id, 
                        s.name AS name,
                        s.ipv4addr AS ipv4addr, 
                        s.ipv6addr AS ipv6addr, 
                        s.snmppasswd AS snmppasswd,
                        i.name AS infrastructure, 
                        s.switchtype AS switchtype, 
                        s.model AS model,
                        s.active AS active, 
                        s.notes AS notes, 
                        s.lastPolled AS lastPolled,
                        s.hostname AS hostname, 
                        s.os AS os, 
                        s.osDate AS osDate, 
                        s.osVersion AS osVersion,
                        s.serialNumber AS serialNumber, 
                        s.mauSupported AS mauSupported,
                        v.id AS vendorid, 
                        v.name AS vendor, 
                        c.id AS cabinetid, 
                        c.name AS cabinet, 
                        s.asn as asn, 
                        s.loopback_ip as loopback_ip, 
                        s.loopback_name as loopback_name
                FROM Entities\\Switcher s
                LEFT JOIN s.Infrastructure i
                LEFT JOIN s.Cabinet c
                LEFT JOIN s.Vendor v
                
                WHERE 1 = 1";

        if( $id ) {
            $dql .= " AND s.id = " . (int)$id;
        }


        if( isset( $params[ "params" ][ "activeOnly" ] ) && $params[ "params" ][ "activeOnly" ] ){
            $dql .= " AND s.active = true";
        }

        if( isset( $feParams->listOrderBy ) ) {
            $dql .= " ORDER BY " . $feParams->listOrderBy . ' ';
            $dql .= isset( $feParams->listOrderByDir ) ? $feParams->listOrderByDir : 'ASC';
        }

        $query = $this->getEntityManager()->createQuery( $dql );

        return $query->getArrayResult();
    }

}
