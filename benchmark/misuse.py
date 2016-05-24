import yaml
from os.path import isdir, isfile, join, basename

class Misuse:
    META_FILE = "meta.yml"
    
    @staticmethod
    def ismisuse(path: str) -> bool:
        return isdir(path) and isfile(join(path, Misuse.META_FILE))
    
    def __init__(self, path: str):
        self.path = path
        self.name = basename(path)
        self.meta_file = join(path, Misuse.META_FILE)
    
    @property
    def meta(self):
        if getattr(self, '_META', None) is None:
            stream = open(self.meta_file, 'r')
            try:
                self._META = yaml.load(stream)
            finally:
                stream.close()
        return self._META
    
    def __str__(self):
        return self.name