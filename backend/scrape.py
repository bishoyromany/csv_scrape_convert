from selenium import webdriver
# from selenium.webdriver.common.by import By
# from selenium.webdriver.support.ui import WebDriverWait
# from selenium.webdriver.support import expected_conditions as EC
import sys
from time import sleep
from bs4 import BeautifulSoup
# from adblockparser import AdblockRules
import json
import urllib.request as urllib2

try:
    import re2 as re
except ImportError:
    import re

ENABLE_BROWSER = False


url = sys.argv[1]

def getLinks(html):
    soup = BeautifulSoup(html)
    links = []
    for link in soup.findAll('a', attrs={'href': re.compile("^http://")}):
        links.append(link.get('href'))
    for link in soup.findAll('a', attrs={'href': re.compile("^https://")}):
        links.append(link.get('href'))
    for link in soup.findAll('iframe', attrs={'src': re.compile("^https://")}):
        links.append(link.get('src'))
    for link in soup.findAll('iframe', attrs={'src': re.compile("^http://")}):
        links.append(link.get('src'))
    for link in soup.findAll('script', attrs={'src': re.compile("^https://")}):
        links.append(link.get('src'))
    for link in soup.findAll('script', attrs={'src': re.compile("^http://")}):
        links.append(link.get('src'))
    return links

# def hasAds(links):
#     test = False
#     file1 = open('easylist_general_block.txt', 'r') 
#     Lines = file1.readlines() 
#     rules = AdblockRules(Lines)
#     print(rules)
#     for link in links:
#         if rules.should_block(link):
#             test = link
#             break
#     return test 
            
if ENABLE_BROWSER == False:
    print(url)
    print(urllib2.urlopen(url))
    print(json.dumps(getLinks(urllib2.urlopen(url))))
else:
    driver = webdriver.Chrome(executable_path="chromedriver.exe")
    driver.get(url)
    sleep(5)
    print(json.dumps(getLinks(driver.page_source)))
    driver.quit()


exit()
