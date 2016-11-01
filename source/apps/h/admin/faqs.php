<?php

$langs					=	[];

foreach (DBTranslator::$LANGUAGES as $id => $v)
{
	$langs[$v[0]]	=	$v[2][0];
}

$page->title	=	"F.A.Q.s";

?>
<div class=ButtonBar>
	<?=FormSelect('lang1', $langs, 'en-us');?>
	<?=FormSelect('lang2', $langs, 'zh-cn');?>
	<a class="Color1 ListTopicsButton">List Topics</a>
	<a class="Color2 AddTopicButton">Add Topic</a>
	<a class="Color3 DeleteTopicButton">Delete Topic</a>
</div>

<br class=Cleared>

<div class="FAQResults"></div>

<script>

FAQ	=	{
	topicid:	'',
	items:		[],
	selector:	'.FAQResults',

	// ------------------------------------------------------------------
	ListTopics: function()
	{
		P.LoadingAPI(
			FAQ.selector,
			'faqs/listtopics',
			{
				langs:	[$$('.lang1').value, $$('.lang2').value]
			},
			function(d, resultsdiv)
			{
				if (!d.items.length)
				{
					resultsdiv.fill('No topics found.');
					return;
				}
				
				FAQ.topics	=	d.items;
				
				var ls	=	['<table class="Styled Width100">',
					'<thead>',
						'<th>' + P.Selected('.lang1').text + '</th>',
						'<th>' + P.Selected('.lang2').text + '</th>',
						'<th></th>',
					'</thead>',
					'<tbody>'
				];
				
				for (var i = 0, l = d.items.length; i < l; i++)
				{
					var item	=	d.items[i];
					
					ls.addtext([
						'<tr class=HoverHighlight>',
							'<td onclick="FAQ.List(\'' + item.topicid + '\')">' + item.question_1 + '</td>',
							'<td onclick="FAQ.List(\'' + item.topicid + '\')">' + item.question_2 + '</td>',
							'<td class="Color1 Center" onclick="FAQ.Delete(0, \'' + item.topicid + '\')">Delete</td>',
						'</tr>',
					]);
				}
				
				ls.push('</tbody></table>');
				resultsdiv.ht(ls.join('\n'));
			}
		);
	},
	
	// ------------------------------------------------------------------
	List: function(topicid)
	{
		var lang1		=	$$('.lang1').value;
		var lang2		=	$$('.lang2').value;
		
		P.LoadingAPI(
			FAQ.selector,
			'faqs/list',
			{
				topicid:	topicid,
				langs:		[lang1, lang2]
			},
			function(d, resultsdiv)
			{
				if (!d.items.length)
				{
					resultsdiv.fill('No topics found.');
					return;
				}
				
				FAQ.topicid	=	topicid;
				FAQ.items		=	d.items;
				FAQ.Display();
			}
		);
	},
	
	// ------------------------------------------------------------------
	Edit:	function(td)
	{
		var vals	=	{
			lang:			$$('.lang1').value,
			section:	'',
			question:	'',
			answer:		''
		};
		
		if (td)
		{
			td	=	$(td);
			vals.lang				=	td.get('%lang');
			vals.sectionid	=	td.up('tr').get('%sectionid');
			vals.section		=	$$('.Section', td).innerHTML;
			vals.question		=	$$('.Question', td).innerHTML;
			vals.answer			=	$$('.Answer', td).innerHTML;
		}
	
		P.EditPopup(
			[
				['section', 'text', vals.section, 'Section'],
				['question', 'multitext', vals.question, 'Question'],
				['answer', 'multitext', vals.answer, 'Answer'],
			],
			function(formdiv, values)
			{
				if (!values.question)
					return;
					
				FAQ.Save(vals.lang, vals.sectionid, values.section, values.question, values.answer);			
				return 1;
			},
			{
				fullscreen:	1
			}
		);
	},
	
	// ------------------------------------------------------------------
	Save:	function(lang, sectionid, section, question, answer)
	{
		sectionid	=	sectionid	|| 0;
	
		P.LoadingAPI(
			FAQ.selector,
			'faqs/save',
			{
				lang:					lang,
				topicid:			FAQ.topicid,
				sectionid:		sectionid,
				section:			section,
				question:			question,
				answer:				answer
			},
			function(d, resultsdiv)
			{
				var found	=	0;
			
				for (var i = 0, l = FAQ.items.length; i < l; i++)
				{
					if (FAQ.items[i].sectionid == d.sectionid)
					{
						FAQ.items[i]	=	d.item;
						found	=	1;
						break;
					}
				}
				
				if (!found)
					FAQ.items.push(d.item);
				
				FAQ.List(d.topicid);
			}
		);
	},
	
	// ------------------------------------------------------------------
	Delete:	function(td, topicid)
	{
		P.LoadingAPI(
			FAQ.selector,
			'faqs/delete',
			{
				topicid:		topicid ? topicid : FAQ.topicid,
				sectionid:	td ? $(td).up('tr').get('%sectionid') : '',
			},
			function(d, resultsdiv)
			{
				if (topicid)
				{
					FAQ.List(topicid);
				} else
				{
					FAQ.ListTopics();
				}
			}
		);
	},
	
	// ------------------------------------------------------------------
	Display:	function()
	{
		var lang1		=	$$('.lang1').value;
		var lang2		=	$$('.lang2').value;
		
		var ls	=	['<table class="FAQ Styled Width100">',
			'<thead>',
				'<th></th>',
				'<th>' + P.Selected('.lang1').text + '</th>',
				'<th>' + P.Selected('.lang2').text + '</th>',
			'</thead>',
			'<tbody>'
		];
		
		FAQ.items.sort(
			function(a, b)
			{
				if (a.section_1 > b.section_1)
					return 1;
					
				if (b.section_1 > a.section_1)
					return -1;
				
				return 0;
			}
		);
		
		for (var i = 0, l = FAQ.items.length; i < l; i++)
		{
			var item	=	FAQ.items[i];
			
			ls.addtext([
				'<tr data-sectionid="' + item.sectionid + '">',
					'<td>' + (i + 1) + '</td>',
					'<td class=HoverHighlight onclick="FAQ.Edit(this)" data-lang="' + lang1 + '">',
						'<div class=Section>' + item.section_1 + '</div>',
						'<div class=Question>' + item.question_1 + '</div>',
						'<div class=Answer>' + item.answer_1 + '</div>',
					'</td>',
					'<td class=HoverHighlight onclick="FAQ.Edit(this)" data-lang="' + lang2 + '" data-sectionid="' + item.sectionid + '">',
						'<div class=Section>' + item.section_2 + '</div>',
						'<div class=Question>' + item.question_2 + '</div>',
						'<div class=Answer>' + item.answer_2 + '</div>',
					'</td>',
					'<td class="Color1 Center" onclick="FAQ.Delete(this)">Delete</td>',
				'</tr>',
			]);
		}
		
		ls.push('</tbody></table>');
		$(FAQ.selector).ht(ls.join('\n'));
		
		P.FlexInput();
	}
};

// ********************************************************************
_loader.OnFinished(
	function()
	{
		FAQ.ListTopics();
		
		P.OnClick('.AddTopicButton', function() { FAQ.Edit(); });
	}
);

</script>
