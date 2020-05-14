# Bot Usage 
1. Install Xampp For Windows From https://www.apachefriends.org/index.html
2. Install Python 3 For Windows From https://www.python.org/downloads/
3. Make Sure That Python Exist In The Windows Pathes, to Check This Run ``` python --version ``` If Works Fine Then all is good, Else Go To https://superuser.com/questions/143119/how-do-i-add-python-to-the-windows-path To Know How to do this step 
4. Run Download The Bot From https://github.com/bishoyromany/csv_scrape_convert Extract Files And Move Them To htdocs Folder
5. Go To The Bot Folder inside htdocs folder, then go to backend folder then run following commands 
    ```
    pip install selenium
    pip install beautifulsoup4 
    ```
6. Open Xampp, And Run The Server, Then Go To http://127.0.0.1/bot_folder_name/ And Fill Form Details Then Run The Script

# Additional Information

* You can Enable Web Server Option, To Let The Website Content Full Load, By Editing **ENABLE_BROWSER** From **False** To **True** In path-to-xampp/htdocs/bot_folder_name/backend/scrape.py
    
    ```python
        try:
            import re2 as re
        except ImportError:
            import re

        ENABLE_BROWSER = False


        url = sys.argv[1]
    ```

    ```python
        try:
            import re2 as re
        except ImportError:
            import re

        ENABLE_BROWSER = True


        url = sys.argv[1]
    ```

### That's It All, Thanks.