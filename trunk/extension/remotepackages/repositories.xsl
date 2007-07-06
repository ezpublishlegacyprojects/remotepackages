<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:template match="/">
		<html>
			<head/>
			<body style="font-family: sans-serif;">
			<h1>Repositories</h1>
		<table style="width:100%; background-color: #EEE;empty-cells:hide;">
		<tr style="background-color: #FFF;">
		<th style="text-align: left;width:1%;">Name</th>
		</tr>

	<xsl:for-each select="repositories/repository">
    <tr>
      <td><xsl:value-of select="@repository-id"/></td>
      <td>
      <A>
      <xsl:attribute name="HREF">
        <xsl:value-of select="../@server-url"/>
        <xsl:text>/</xsl:text>
        <xsl:value-of select="@repository-id"/>
      </xsl:attribute>
      <xsl:text>view</xsl:text>
      </A>
      </td>
    </tr>
    </xsl:for-each>

		</table>
			</body>
		</html>
	</xsl:template>


</xsl:stylesheet>