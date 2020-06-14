// 复制粘贴功能
import Clipboard from 'clipboard'
const clipboard = {
  data() {
    return {
    };
  },

  methods: {
    onCopy() {
      let clipboard = new Clipboard(".a-copy");
      clipboard.on("success", e => {
        this.$Toast.success("复制成功");
        clipboard.destroy();
      });
      clipboard.on("error", e => {
        this.$Toast.fail("复制失败");
        clipboard.destroy();
      });
    }
  }
};

export default clipboard
