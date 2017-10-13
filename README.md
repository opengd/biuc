# biuc
Bing Image Update Cache

biuc is a cache and service to by http get ask for images from Bing Cognitiv Image Search service.

For more information on Bing Image Search and how to run it in Azure, please visit:

[https://azure.microsoft.com/en-us/services/cognitive-services/bing-image-search-api/]

And I should also mark that this is a project that have not put security at first. It's quite relaxed pice of code. 

## settings.php

In settings.php is used to store db settings and password/salt for hash to use when asking for data. Also your bing azure key to use to for bing image search.

## stats.php

Used this ask for db stats. You will recive a json string as response.

```
# curl http://host.to.your.service/biuc/stats.php?h=YOUR_ADMIN_PASSOWORD_FROM_SETTINGS
{"total_count":[{"count(*)":"41377"}],"album_count":[{"count(*)":"37753"}],"artist_count":[{"count(*)":"3624"}],"local_count_false":[{"count(*)":"22"}],"local_count_true":[{"count(*)":"41355"}]}
```

## storelocal.php

This script will check the db for new post and download any new found image url's to the local cache.

```# curl http://host.to.your.service/biuc/storelocal.php?h=YOUR_ADMIN_PASSOWORD_FROM_SETTINGS```

## biuc.php

The main script to be used by clients to ask for images that can be found either by a bing search or in the local cache. You will have to create a hash from the search string and the user password as arguments on enquiring the biuc service.

The hash to send as argument "h" is a Md5, simply generated from your query_string + YOUR_USER_PASSWORD_IN_SETTINGS

Below is a usage example in C#.

```
private static async Task<string> GetBiucArtworkURLAsync(string query)
{
    var client = new HttpClient();

    var key = "YOUR_USER_PASSWORD_IN_SETTINGS";

    var queryHash = CryptographicBuffer.EncodeToHexString(
        HashAlgorithmProvider.OpenAlgorithm(HashAlgorithmNames.Md5).HashData(
            CryptographicBuffer.ConvertStringToBinary(query + key, BinaryStringEncoding.Utf8)));

    // Request parameters
    string encodedQuery = string.Empty;

    using (var content = new FormUrlEncodedContent(new KeyValuePair<string, string>[]{
        new KeyValuePair<string, string>("q", query),
        new KeyValuePair<string, string>("h", queryHash),
    }))
    {
        encodedQuery = content.ReadAsStringAsync().Result;
    }

    var uri = "http://host.to.your.service/biuc/?" + encodedQuery;

    Debug.WriteLine(uri);

    HttpResponseMessage response = null;
    var numberOfGetAttemps = 3;

    while (response == null && numberOfGetAttemps > 0)
    {
        try
        {
            response = await client.GetAsync(uri);
        }
        catch (Exception ex)
        {
            Debug.WriteLine(ex.Message);
            numberOfGetAttemps--;
        }
    }

    if (response != null)
    {
        var resp = await response.Content.ReadAsStringAsync();

        Debug.WriteLine(resp);

        var imageJson = new JsonObject();
        if (JsonObject.TryParse(resp, out imageJson)
            && imageJson.ContainsKey("url") && imageJson["url"].ValueType == JsonValueType.String)
        {
            return imageJson["url"].GetString();
        }
    }

    return null;
}
```