<?xml version="1.0" encoding="utf-8"?>
<blocks>
	<block type="modules_<{$module}>_<{$blockName}>" display="" icon="<{$icon}>"
		label="&amp;modules.<{$module}>.bo.blocks.<{$blockName}>.Title;">
		<parameters>
			<parameter name="cmpref" type="modules_<{$documentModule}>/<{$documentName}>" />
			<parameter name="nbitemperpage" type="Integer" default-value="25" />
			<parameter name="enableRating" type="Boolean" default-value="true" />
			<parameter name="showRatingDistribution" type="Boolean" default-value="true" />
			<parameter name="displaySortOptions" type="Boolean" default-value="true" />
			<parameter name="enableEvaluation" type="Boolean" default-value="true" />
			<parameter name="showgravatars" type="Boolean" default-value="false" />
		</parameters>
	</block>
</blocks>