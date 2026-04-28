import os
import urllib.request
import re
import time
from html.parser import HTMLParser

class WordPressDocParser(HTMLParser):
    def __init__(self):
        super().__init__()
        self.recording = False
        self.content = []
        self.div_count = 0
        self.current_tag = None
        self.in_pre = False

    def handle_starttag(self, tag, attrs):
        attrs_dict = dict(attrs)
        classes = attrs_dict.get('class', '').split()
        if tag == 'div' and ('entry-content' in classes or 'wp-block-post-content' in classes):
            self.recording = True
            self.div_count = 1
            return
        
        if self.recording:
            if tag == 'div':
                self.div_count += 1
            self.current_tag = tag
            if tag == 'h1': self.content.append('\n# ')
            elif tag == 'h2': self.content.append('\n## ')
            elif tag == 'h3': self.content.append('\n### ')
            elif tag == 'h4': self.content.append('\n#### ')
            elif tag == 'p': self.content.append('\n')
            elif tag == 'li': self.content.append('\n- ')
            elif tag == 'pre': 
                self.content.append('\n```php\n')
                self.in_pre = True
            elif tag == 'code' and not self.in_pre: 
                self.content.append('`')

    def handle_endtag(self, tag):
        if self.recording:
            if tag == 'div':
                self.div_count -= 1
                if self.div_count == 0:
                    self.recording = False
            elif tag == 'pre':
                self.content.append('\n```\n')
                self.in_pre = False
            elif tag == 'code' and not self.in_pre:
                self.content.append('`')
            elif tag in ['h1', 'h2', 'h3', 'h4', 'p', 'li']:
                self.content.append('\n')

    def handle_data(self, data):
        if self.recording:
            if self.in_pre:
                self.content.append(data)
            else:
                text = data.strip()
                if text:
                    self.content.append(text + " ")

    def get_markdown(self):
        return "".join(self.content)

# Sitemap with slugs and directories
sitemap = [
    {"slug": "intro", "base_url": "https://developer.wordpress.org/plugins/intro/", "pages": ["what-is-a-plugin"]},
    {"slug": "basics", "base_url": "https://developer.wordpress.org/plugins/plugin-basics/", "pages": ["header-requirements", "activation-deactivation-hooks", "best-practices", "determining-plugin-and-content-directories", "including-a-software-license", "uninstall-methods"]},
    {"slug": "security", "base_url": "https://developer.wordpress.org/plugins/security/", "pages": ["checking-user-capabilities", "data-validation", "nonces", "securing-output", "securing-input"]},
    {"slug": "hooks", "base_url": "https://developer.wordpress.org/plugins/hooks/", "pages": ["actions", "filters", "custom-hooks", "advanced-topics"]},
    {"slug": "privacy", "base_url": "https://developer.wordpress.org/plugins/privacy/", "pages": ["adding-the-personal-data-eraser-to-your-plugin", "adding-the-personal-data-exporter-to-your-plugin", "privacy-related-options-hooks-and-capabilities", "suggesting-text-for-the-site-privacy-policy"]},
    {"slug": "admin-menus", "base_url": "https://developer.wordpress.org/plugins/administration-menus/", "pages": ["sub-menus", "top-level-menus"]},
    {"slug": "shortcodes", "base_url": "https://developer.wordpress.org/plugins/shortcodes/", "pages": ["basic-shortcodes", "enclosing-shortcodes", "shortcodes-with-parameters", "tinymce-enhanced-shortcodes"]},
    {"slug": "settings", "base_url": "https://developer.wordpress.org/plugins/settings/", "pages": ["custom-settings-page", "options-api", "settings-api", "using-settings-api"]},
    {"slug": "metadata", "base_url": "https://developer.wordpress.org/plugins/metadata/", "pages": ["managing-post-metadata", "custom-meta-boxes", "rendering-post-metadata"]},
    {"slug": "post-types", "base_url": "https://developer.wordpress.org/plugins/post-types/", "pages": ["registering-custom-post-types", "working-with-custom-post-types"]},
    {"slug": "taxonomies", "base_url": "https://developer.wordpress.org/plugins/taxonomies/", "pages": ["split-terms-wp-4-2", "working-with-custom-taxonomies"]},
    {"slug": "users", "base_url": "https://developer.wordpress.org/plugins/users/", "pages": ["roles-and-capabilities", "working-with-user-metadata", "working-with-users"]},
    {"slug": "rest-api", "base_url": "https://developer.wordpress.org/plugins/rest-api/", "pages": ["rest-api-overview", "routes-endpoints", "requests", "responses-2", "schema", "controller-classes"]},
    {"slug": "javascript", "base_url": "https://developer.wordpress.org/plugins/javascript/", "pages": ["heartbeat-api", "jquery", "ajax", "enqueuing"]},
    {"slug": "cron", "base_url": "https://developer.wordpress.org/plugins/cron/", "pages": ["scheduling-wp-cron-events", "simple-testing", "understanding-wp-cron-scheduling", "hooking-wp-cron-into-the-system-task-scheduler"]},
    {"slug": "i18n", "base_url": "https://developer.wordpress.org/plugins/internationalization/", "pages": ["how-to-internationalize-your-plugin", "security", "localization"]},
    {"slug": "wp-org", "base_url": "https://developer.wordpress.org/plugins/wordpress-org/", "pages": ["detailed-plugin-guidelines", "how-your-readme-txt-works", "plugin-assets", "how-to-use-subversion"]},
    {"slug": "tables", "base_url": "https://developer.wordpress.org/plugins/", "pages": ["creating-tables-with-plugins"]},
    {"slug": "tools", "base_url": "https://developer.wordpress.org/plugins/developer-tools/", "pages": ["debug-bar-and-add-ons", "helper-plugins"]},
    {"slug": "http-api", "base_url": "https://developer.wordpress.org/plugins/", "pages": ["http-api"]},
]

base_dir = "wp_plugin_docs/chapters"

def fetch_and_save(url, save_path):
    print(f"Fetching: {url}")
    try:
        req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
        with urllib.request.urlopen(req) as response:
            html = response.read().decode('utf-8')
            parser = WordPressDocParser()
            parser.feed(html)
            markdown = parser.get_markdown()
            
            if not markdown.strip():
                print(f"Warning: No content found for {url}")
                return

            with open(save_path, 'w') as f:
                f.write(f"# Source: {url}\n\n")
                f.write(markdown)
            print(f"Saved to: {save_path}")
    except Exception as e:
        print(f"Error fetching {url}: {e}")

for section in sitemap:
    section_dir = os.path.join(base_dir, section["slug"])
    os.makedirs(section_dir, exist_ok=True)
    for page in section["pages"]:
        url = section["base_url"] + page + "/"
        save_path = os.path.join(section_dir, f"{page}.md")
        if os.path.exists(save_path):
            print(f"Skipping {page}, already exists.")
            continue
        fetch_and_save(url, save_path)
        time.sleep(0.5) 
