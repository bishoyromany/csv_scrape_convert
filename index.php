<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">
    <title>CSV Convertor Scraper</title>
</head>
<body>  
    <br><br><br><br>
    <div class="container">
        <h1 class="text-center">Please Fill The Following Information</h1>
        <form action="backend/serve.php" method="get" value='https://sellersjsons.com/sellersjson_csv.html'>
            <div class="form-group">
                <label for="url">Website URL</label>
                <input type="text" name="url" id="url" class="form-control" value='https://sellersjsons.com/sellersjson_csv.html'>
            </div>

            <div class="form-group">
                <label for="columns">Columns</label>
                <textarea name="columns" id="columns" class="form-control"  cols="30" rows="5">2-domain,3-seller_type</textarea>
            </div>

            <label for="filterDuplicates">
                Filter Duplicates 
                <input type="checkbox" name="filterDuplicates" id="filterDuplicates" value="1" checked>
            </label> <br>
            <label for="allInOneFile">
                All In One File
                <input type="checkbox" name="allInOneFile" id="allInOneFile" value="1" checked>
            </label> <br>
            <div class="form-group">
                <label for="fileName">File Name</label>
                <input type="text" name="fileName" id="fileName" class="form-control">
            </div>
            <div class="form-group">
                <label for="startFrom">Start From</label>
                <input type="text" name="startFrom" id="startFrom" placeholder="CSV Start Number" class="form-control">
            </div>
            <div class="form-group">
                <label for="stopAt">Stop At</label>
                <input type="text" name="stopAt" id="stopAt" placeholder="CSV Stop Number" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary" name="serve">Start</button>
        </form>
    </div>

    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js" integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI" crossorigin="anonymous"></script>
</body>
</html>