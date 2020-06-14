<template>
  <Layout ref="load" class="member-centre bg-f8" :style="pageStyle">
    <InviteWechat />
    <CustomGroup type="4" :items="items" />
    <Copyright />
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import InviteWechat from "@/components/InviteWechat";
import CustomGroup from "@/components/CustomGroup";
import Copyright from "@/components/Copyright";
import { defaultData } from "./default-custom-data";
export default sfc({
  name: "member-centre",
  data() {
    return {
      items: {},
      page: {}
    };
  },
  computed: {
    pageStyle() {
      return {
        background: this.page.background
      };
    }
  },
  activated() {
    const $this = this;
    $this.$store
      .dispatch("getMemberInfo")
      .then(info => {
        $this.info = info;
        $this.info.name = this.getName(info);
        $this.info.level_name = $this.info.level_name || "默认等级";
        $this.getCustom();
      })
      .catch(() => {
        $this.$refs.load.fail();
      });
  },
  methods: {
    getName({ nick_name, user_name, user_tel }) {
      if (nick_name) return nick_name;
      if (user_name) return user_name;
      if (user_tel) return user_tel;
      return "未设置昵称";
    },
    getCustom() {
      this.$store
        .dispatch("getCustom", {
          type: 4
        })
        .then(({ template_data }) => {
          if (template_data) {
            this.page = template_data.page;
          }
          document.title = this.page.title ? this.page.title : "会员中心";
          this.items = this.initCustomData(template_data);
          this.$refs.load.success();
        })
        .catch(() => {
          this.$refs.load.fail();
        });
    },
    initCustomData(template_data) {
      const { blockchain } = this.$store.state.config.addons;
      const { items } = defaultData;
      const templateItems = template_data ? template_data.items : items;
      let newItems = {};
      const arr = [
        "member_fixed",
        "member_bind_fixed",
        "member_assets_fixed",
        "member_order_fixed"
      ];
      for (let key in templateItems) {
        const item = templateItems[key];
        if (
          item.id == arr[0] &&
          (!item.params || !item.params.styletype) &&
          item.id != arr[1] &&
          item.id != arr[2] &&
          item.id != arr[3]
        ) {
          // 处理旧数据没有相关数据问题，采用默认数据
          for (let i in items) {
            newItems[i] = items[i];
            newItems[i].info = this.info;
          }
        } else {
          newItems[key] = item;
          if (arr.indexOf(item.id) != -1) {
            newItems[key].info = this.info;
          }
          // 处理旧数据没有数字钱包相关数据问题
          if (newItems[key].id == "member_assets_fixed") {
            if (blockchain && !newItems[key].data["C0_blockchain"])
              newItems[key].data["C0_blockchain"] = {
                is_show: "1",
                key: "blockchain",
                name: "数字钱包",
                no_addons: "0",
                text: "数字钱包"
              };
          }
        }
      }
      return newItems;
    }
  },
  components: {
    InviteWechat,
    CustomGroup,
    Copyright
  }
});
</script>

<style scoped>
</style>
