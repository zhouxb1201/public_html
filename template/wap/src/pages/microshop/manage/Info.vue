<template>
  <Layout ref="load" class="microshop-info bg-f8">
    <Navbar :isMenu="false" />
    <van-cell-group>
      <van-field label="微店名称" placeholder="微店名称" v-model="mic_name" />
      <van-field label="微店介绍" type="textarea" placeholder="请输入微店介绍" v-model="mic_introduce" />
    </van-cell-group>
    <div class="foot-btn-group">
      <van-button size="normal" type="danger" round block @click="onSave">保存</van-button>
    </div>
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import { setSession, getSession } from "@/utils/storage";
import { GET_SHOPSET } from "@/api/microshop";
export default sfc({
  name: "microshop-info",
  data() {
    return {
      mic_name: null,
      mic_introduce: null
    };
  },
  computed: {
    microshop_name() {
      return getSession("set").microshop_name
        ? getSession("set").microshop_name
        : "";
    },
    microshop_introduce() {
      return getSession("set").microshop_introduce
        ? getSession("set").microshop_introduce
        : "";
    },
    microshop_logo() {
      return getSession("set").microshop_logo
        ? getSession("set").microshop_logo
        : "";
    },
    shopRecruitment_logo() {
      return getSession("set").shopRecruitment_logo
        ? getSession("set").shopRecruitment_logo
        : "";
    }
  },
  mounted() {
    this.mic_name = this.microshop_name;
    this.mic_introduce = this.microshop_introduce;
    this.$refs.load.success();
  },
  methods: {
    onSave() {
      const $this = this;
      const params = {
        microshop_logo: $this.microshop_logo,
        shopRecruitment_logo: $this.shopRecruitment_logo,
        microshop_name: $this.mic_name,
        microshop_introduce: $this.mic_introduce
      };

      GET_SHOPSET(params)
        .then(res => {
          $this.$store.dispatch("getMicroshopInfo").then();
          $this.$router.go(-1);
        })
        .catch(() => {
          $this.$refs.load.fail();
        });
    }
  }
});
</script>

<style scoped>
</style>
