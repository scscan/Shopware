{extends file='backend/index/parent.tpl'}

{block name="backend_index_css" replace}
<link href="{link file='backend/_resources/javascript/ext-4.0.0/resources/css/ext-all.css'}" rel="stylesheet" type="text/css" />
{/block}

{block name="backend_index_javascript" replace}
	<script type="text/javascript" src="{link file='backend/_resources/javascript/ext-4.0.0/bootstrap.js'}"></script>
	<script type="text/javascript">
		Ext.Loader.setConfig({ enabled:true});
		Ext.Loader.setPath('Ext.app', '{link file="backend/_resources/javascript/plugins"}');
		Ext.require([
			'Ext.layout.container.*',
			'Ext.resizer.Splitter',
			'Ext.fx.target.Element',
			'Ext.fx.target.Component',
			'Ext.window.Window',
			'Ext.app.Portal',
			'Ext.app.PortalColumn',
			'Ext.app.PortalPanel',
			'Ext.app.Portlet',
			'Ext.app.PortalDropZone',
			'Ext.app.GridPortlet',
			'Ext.app.ChartPortlet'
		]);
		Ext.define('Ext.app.Portal', {

			extend: 'Ext.container.Viewport',


			initComponent: function(){
				
				Ext.apply(this, {
					id: 'app-viewport',
					title: 'Test',
					layout: {
						type: 'border',
						padding: '0 0 11 0'
					},
					items: [{
						xtype: 'portalpanel',
						bodyStyle: "background: transparent url({link file="templates/_default/backend/_resources/images/index/background_sample.jpg"}) repeat-x scroll !important",

						region: 'center',
						layout: 'border',
						items: [

						{
							id: 'colTemp',
							flex: 1,
							items: [
								{
									html: '{$error}'
								}
							]
						}
						]
					}
					]
				});

				this.callParent(arguments);
			}
		});
        Ext.onReady(function(){
            Ext.create('Ext.app.Portal');
        });
	</script>
{/block}