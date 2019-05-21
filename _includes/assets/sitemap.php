<?php

	function subPages( $rs, $parent_id = 0 ) {
		$return = array();
		
		foreach ( $rs as $i => $p ) {
			if ( $parent_id == $p['parent_id'] ) {
				$return[$p['page_id']] = $p;
				$return[$p['page_id']]['children'] = subPages( $rs, $p['page_id'] );
			}
		}
		
		return $return;
	}
	
	function makeSiteMapList( $pages ) {
				
		$return = '';
		
		foreach ( $pages as $i => $p ) {
				$class = '';
				
				if ( count( $p['children'] ) > 0 ) {
					$class = 'hassub';					
				}
				
				
				$return .= <<<RETURN
					<li class = "{$class}">
						<a href = "{$p['url']}">{$p['title']}</a>
RETURN;

				if ( count( $p['children'] ) > 0 ) {
					
					$return .= makeSiteMapList( $p['children'] );
				}
				
				$return .= <<<RETURN
					</li>
RETURN;
				
			
		}
		
		if ( $return ) {
			$return = '<ul class="sitemap">'.$return.'</ul>';
		}
		return $return;
	}


	$sql = <<<EOSQL
		SELECT 
			p.page_id, p.parent_id, p.title, p.is_landing_page, p.include_inside_nav, 
			p.keywords, p.description, p.body, p.sort, p.url, p.redirect, p.asset, 
			p.created, p.modified, p.media_id, p.og_title, p.og_image_media_id, 
			p.og_description, m.filename, m2.filename AS og_image
		FROM pages p
		LEFT JOIN media m ON m.media_id = p.media_id
		LEFT JOIN media m2 ON m2.media_id = p.og_image_media_id
		WHERE p.status = 'active'
EOSQL;

	if ( $_SERVER['REQUEST_URI'] == '/sitemap.xml' ) {
	
		$sql .= ' ORDER BY p.sort ';
		
	} else {
	
		$sql .= ' ORDER BY p.url ';
		
	}


	$rs = $db->fetch_array( $sql );
	
	
	if ( $_SERVER['REQUEST_URI'] == '/sitemap.xml' ) {
	
		//apache_setenv('no-gzip', '1');
	
		header( 'Content-Type', 'text/xml; charset=utf-8' );
	
	
		$xml = <<<XML
		
			<?xml version="1.0" encoding="UTF-8"?>
			<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

XML;

	foreach ( $rs as $p ) {
		$xml .= <<<CONTENT
			<url>
				<loc>http://{$_SERVER['SERVER_NAME']}{$p['url']}</loc>
				<priority>0.5</priority>
			</url>	
CONTENT;
	
	}

	$xml .= '</urlset>';
	
	echo $xml;
	exit();

} else {
	
	$rs = subPages( $rs );

	$pages = makeSiteMapList( $rs );
	
	$_TEMPLATE['CONTENT'] .= $pages;
	
}
?>