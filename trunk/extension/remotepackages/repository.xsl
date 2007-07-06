<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:template match="/">
		<html>
			<head/>
			<body style="font-family: sans-serif;">
			<h1>
			 <xsl:text>Packages of </xsl:text>
			 <xsl:value-of select="repository/@repository-id"/>
			</h1>
		<table style="width:100%; background-color: #EEE;empty-cells:hide;">
		<tr style="background-color: #FFF;">
		<th style="text-align: left;">Name</th>
		<th style="text-align: left;">Version</th>
		<th style="text-align: left;">Vendor</th>
		<th style="text-align: left;">Summary</th>
		<th style="text-align: left;">Description</th>
		<th style="text-align: left;">Download Link</th>
		</tr>

	<xsl:for-each select="repository/package">
    <tr>
      <td><xsl:value-of select="name"/></td>
      <td><xsl:value-of select="version/number"/>-<xsl:value-of select="version/release"/></td>
      <td><xsl:value-of select="vendor"/></td>
      <td><xsl:value-of select="summary"/></td>
      <td><xsl:value-of select="description"/></td>
      <td>
      <A>
      <xsl:attribute name="HREF">
        <xsl:value-of select="../@repository-url"/><xsl:text>/</xsl:text>
        <xsl:value-of select="name"/>
        <xsl:text>/</xsl:text>
        <xsl:value-of select="version/number"/>-<xsl:value-of select="version/release"/>
      </xsl:attribute>
      <xsl:text>Download</xsl:text>
      </A>
      </td>
    </tr>
    </xsl:for-each>

		</table>
			</body>
		</html>
	</xsl:template>


</xsl:stylesheet>