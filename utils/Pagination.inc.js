function pagination(page,url,query,id)
{
	 $.ajax({
			type: 'GET',
			url: url,
			data: "&page="+page+"&"+query,
			success:
			function(data)
			{
				$("#"+id).html(data);
			}
		  });
}
