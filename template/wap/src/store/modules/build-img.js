const { html2canvas } = require('@/assets/scripts/html2canvas')

const buildImg = {
  state: {
  },
  mutations: {
  },
  actions: {
    /**
     * 生成海报图
     * @param {document} dom 
     */
    buildImg({ commit }, dom) {
      return new Promise((resolve, reject) => {
        function getOffsetSum(elem) {
          var top = 0, left = 0
          while (elem) {
            top = top + parseInt(elem.offsetTop)
            left = left + parseInt(elem.offsetLeft)
            elem = elem.offsetParent
          }
          return { top: top, left: left }
        }
        const canvasBox = document.createElement("canvas");
        const scale = 2;
        const rect = getOffsetSum(dom);
        const context = canvasBox.getContext("2d");
        context.scale(scale, scale);
        context.translate(-rect.left, -rect.top);
        const options = {
          canvas: canvasBox,
          backgroundColor: "#ffffff",
          useCORS: true,
          logging: false
        };
        html2canvas(dom, options).then(canvas => {
          let dataURL = canvas.toDataURL("image/jpeg");
          resolve(dataURL)
        }).catch(() => {
          reject()
        })
      })
    }
  }
}

export default buildImg
