describe("Api", function() {
  var apiUrl;

  beforeEach(function() {
	  apiUrl=window.apiUrl;
  });

  it("should be able to create a document", function() {
    var doc={
			_id:'new doc ' + new Date().getTime(),
			a:'A',
			b:'B',
			bool:true
		};
		return fetch(apiUrl,{
			method:'POST',
			body:JSON.stringify({doc:doc,table:'table2'})
		})
		.then(res => res.json())
		.then(res => {
			//console.log('res',res);
			expect(res._rev).toBeDefined();
			return fetch(apiUrl+'?_id='+encodeURIComponent(doc._id)+'&table=table2');
		})
		.then(res=>res.json())
		.then(res => {
			//console.log('res',res);
			var retrievedDoc=res.doc;
			expect(retrievedDoc.a).toBe('A');
		});
  });
	it("should be able to update a document",function(){
		var doc = {
			_id:'update doc ' + new Date().getTime(),
			a:'A',
			b:'B',
			bool:true
		};
		var rev='';
		//save the doc
		return fetch(apiUrl,{
			method:'POST',
			body:JSON.stringify({doc:doc,table:'table2'})
		})
		.then(res =>{ 
			console.log(res);
			return res.json();
		})
		.then(res => {
			console.log('res',res);
			expect(res._rev).toBeDefined();
			rev=res._rev;
			doc._rev=rev;
			doc.c='C';
			//save the doc again
			return fetch(apiUrl,{
				method:'POST',
				body:JSON.stringify({doc:doc,table:'table2'})
			});
		})
		.then(res => {
			console.log(res);
			return res.json();
		})
		.then(res => {
			console.log(' updated res',res);
			expect(res._rev).notToBe(rev);

		});
	})
});