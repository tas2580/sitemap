
<xsl:stylesheet version="2.0" xmlns:html="http://www.w3.org/TR/REC-html40" xmlns:sitemap="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" version="1.0" encoding="utf-8" indent="yes"/>
<xsl:template match="/">
	<html lang="de" xmlns="http://www.w3.org/1999/xhtml" dir="ltr" xml:lang="de">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<style>
		</style>
		
		<title>XML Sitemap</title>
	</head>
	<body>
		<header>
			XML Sitemap
		</header>
		<main>
			<h2>XML Sitemap</h2>
			<p>This sitemap contains <b><xsl:value-of select="count(/*/*)"/></b> URLs</p>
			<xsl:apply-templates/>
		</main>
	</body>
	</html>
</xsl:template>

<xsl:template match="/*/*">
	<xsl:variable name="SitemapURL"><xsl:value-of select="*"/></xsl:variable>
	<ul>
		<li><a href="{$SitemapURL}"><span><xsl:value-of select="*"/></span></a></li>
	</ul>
</xsl:template>
</xsl:stylesheet>
