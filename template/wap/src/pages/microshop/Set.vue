<template>
  <Layout ref="load" class="microshop-set bg-f8">
    <Navbar />
    <van-cell-group class="mb-10">
      <van-cell title="微店信息" is-link to="/microshop/info" />
      <van-cell title="微店Logo" is-link class="avatar-cell" to="/microshop/shoplogo">
        <div class="img">
          <img :src="microshop_logo" :onerror="$ERRORPIC.noAvatar" />
        </div>
      </van-cell>
      <van-cell title="店招Logo" is-link class="avatar-cell" to="/microshop/recruitmentlogo">
        <div class="img">
          <img :src="shopRecruitment_logo" :onerror="$ERRORPIC.noAvatar" />
        </div>
      </van-cell>
    </van-cell-group>
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import { isEmpty } from "@/utils/util";
export default sfc({
  name: "microshop-set",
  data() {
    return {};
  },
  computed: {
    set() {
      return this.$store.state.microshop.set;
    },
    microshop_logo() {
      const { set } = this;
      return set && set.microshop_logo ? set.microshop_logo : " ";
    },
    shopRecruitment_logo() {
      const { set } = this;
      return set && set.shopRecruitment_logo ? set.shopRecruitment_logo : " ";
    }
  },
  mounted() {
    this.$store
      .dispatch("getMicroshopInfo")
      .then(res => {
        this.$refs.load.success();
      })
      .catch(error => {
        this.$refs.load.fail();
      });
  },
  methods: {}
});
</script>

<style scoped>
.mb-10 {
  margin-bottom: 10px;
}

.avatar-cell {
  display: flex;
  align-items: center;
}

.avatar-cell .img {
  width: 50px;
  height: 50px;
  overflow: hidden;
  border-radius: 50%;
  float: right;
  background: #f9f9f9;
}

.avatar-cell .img img {
  width: 100%;
  height: 100%;
}
</style>
