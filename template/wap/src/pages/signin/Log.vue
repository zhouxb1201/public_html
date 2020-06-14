<template>
  <div class="signin-log bg-f8">
    <Navbar />
    <List
      v-model="loading"
      :finished="finished"
      :error.sync="error"
      :is-empty="isListEmpty"
      @load="loadList"
    >
      <van-cell :value="item.sign_in_time" v-for="(item,index) in list" :key="index">
        <div slot="title" class="text">
          <div class="positive">{{item.name}}</div>
          <div class="fs-12 text-regular">签到成功</div>
        </div>
      </van-cell>
    </List>
  </div>
</template>

<script>
import sfc from "@/utils/create";
import { GET_SIGNINLOG } from "@/api/signin";
import { list } from "@/mixins";
export default sfc({
  name: "signin-log",
  data() {
    return {};
  },
  mixins: [list],
  computed: {},
  mounted() {
    if (this.$store.state.config.addons.signin) {
      this.loadList();
    } else {
      this.$refs.load.fail({
        errorText: "未开启签到应用",
        showFoot: false
      });
    }
  },
  methods: {
    loadList(init) {
      const $this = this;
      GET_SIGNINLOG($this.params)
        .then(({ data }) => {
          let list = data.data ? data.data : [];
          $this.pushToList(list, data.page_count, init);
        })
        .catch(() => {
          $this.loadError();
        });
    }
  },
  components: {}
});
</script>

<style scoped>
.text {
  line-height: 1.2;
}

.positive {
  color: #4b0;
}

.negative {
  color: #ff454e;
}
</style>


