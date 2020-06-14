
const blockchain = {
  data() {
    return {

    }
  },
  created() {
    this.pageType = this.$route.params.type
  },
  mounted() {
    if (this.$store.state.config.addons.blockchain && (this.$store.getters.isBingFlag && this.$store.getters.isBindMobile)) {
      this.loadBaseData().then(data => {
        this.loadData(data)
      })
    } else {
      this.$refs.load.fail({ errorText: this.$store.getters.isBingFlag && this.$store.getters.isBindMobile ? '未开启区块链应用' : "平台账号体系不支持区块链应用", showFoot: false });
    }
  },
  methods: {
    loadBaseData() {
      return new Promise((resolve, reject) => {
        this.$store
          .dispatch("getBlockchainSet")
          .then(({ wallet_type }) => {
            const arr = wallet_type.split(",");
            if (this.pageType == "eth") {
              if (arr[0] == 1) {
                this.$store
                  .dispatch("getEthInfo")
                  .then(({ code, data }) => {
                    if (code == 1) {
                      resolve(data)
                      this.$refs.load.success();
                    } else {
                      this.$refs.load.fail({
                        errorText: "暂无钱包",
                        errorBtnText: "返回数字资产",
                        errorBtnEvent: false,
                        btnLink: "/blockchain"
                      });
                    }
                  })
                  .catch(() => {
                    reject()
                    this.$refs.load.fail();
                  });
              } else {
                this.$Toast("未开启ETH钱包！");
                this.$router.replace("/blockchain");
              }
            } else if (this.pageType == "eos") {
              if (arr[0] == 2 || arr[1] == 2) {
                this.$store
                  .dispatch("getEosInfo")
                  .then(({ code, data }) => {
                    if (code == 1) {
                      resolve(data)
                      this.$refs.load.success();
                    } else {
                      this.$refs.load.fail({
                        errorText: "暂无钱包",
                        errorBtnText: "返回数字资产",
                        errorBtnEvent: false,
                        btnLink: "/blockchain"
                      });
                    }
                  })
                  .catch(() => {
                    reject()
                    this.$refs.load.fail();
                  });
              } else {
                this.$Toast("未开启EOS钱包！");
                this.$router.replace("/blockchain");
              }
            } else {
              this.$refs.load.fail({
                errorType: '404',
                errorText: "很抱歉，找不到你要访问的页面",
                errorBtnText: "返回数字资产",
                errorBtnEvent: false,
                btnLink: "/blockchain"
              });
            }
          })
          .catch(() => {
            reject()
            this.$refs.load.fail();
          });
      })
    }
  }
}

export default blockchain
