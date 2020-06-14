<template>
  <Layout ref="load" class="shop-home" :style="pageStyle">
    <InviteWechat />
    <CustomGroup type="2" :items="items" />
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import InviteWechat from "@/components/InviteWechat";
import CustomGroup from "@/components/CustomGroup";
import { GET_SHOPINFO, SET_COLLECTSHOP, CANCEL_COLLECTSHOP } from "@/api/shop";
import { filterUriParams } from "@/utils/util";
export default sfc({
  name: "shop-home",
  data() {
    return {
      info: {},
      items: {},
      page: {}
    };
  },
  watch: {
    "$route.params.shopid": function(e) {
      if (e && e !== undefined) {
        this.loadData();
      }
    }
  },
  computed: {
    pageStyle() {
      return {
        background: this.page.background
      };
    }
  },
  mounted() {
    if (this.$store.state.config.addons.shop) {
      this.loadData();
    } else {
      this.$refs.load.fail({ errorText: "未开启店铺应用", showFoot: false });
    }
  },
  methods: {
    loadData() {
      const $this = this;
      if ($this.$store.state.config.addons.shop) {
        GET_SHOPINFO($this.$route.params.shopid)
          .then(({ data }) => {
            $this.info = data;
            $this.onShare({
              title: `${$this.info.shop_name} - ${$this.$store.getters.config.mall_name}`,
              desc: `我刚刚在${$this.$store.getters.config.mall_name}发现了一个很不错的店铺，赶快来看看吧。`,
              imgUrl: $this.info.shop_logo,
              link:
                $this.$store.state.domain +
                "/wap" +
                $this.$route.path +
                filterUriParams($this.$route.query, "extend_code")
            });
            $this.getCustom();
          })
          .catch(e => {
            $this.$refs.load.fail();
          });
      } else {
        $this.$refs.load.fail({ errorText: "未开启店铺应用", showFoot: false });
      }
    },
    getCustom() {
      this.$store
        .dispatch("getCustom", {
          type: 2,
          shop_id: this.$route.params.shopid
        })
        .then(data => {
          this.items = data.template_data ? data.template_data.items : {};
          for (let i in this.items) {
            if (this.items[i].id == "shop_head") {
              this.items[i].info = this.info;
            }
          }
          this.page = data.template_data ? data.template_data.page : {};
          if (this.page.title) {
            document.title = this.page.title;
          }
          this.$refs.load.success();
        })
        .catch(() => {
          this.$refs.load.fail();
        });
    }
  },
  components: {
    InviteWechat,
    CustomGroup
  }
});
</script>

<style scoped>
</style>
