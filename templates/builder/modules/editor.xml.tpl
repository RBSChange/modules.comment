<?xml version="1.0" encoding="UTF-8"?>
<panels>
	<xul>
		<javascript>
			<constructor><![CDATA[
				// Check comment module existence.
				var controller = document.getElementById("wcontroller");
    			if (controller.checkModuleVersion('comment', '3.0.0'))
				{
					this.addTab('comments', '&modules.comment.bo.doceditor.tab.Comments;', 'comments');
				}
			]]></constructor>
		</javascript>
	</xul>	 	
</panels>